<?php

declare(strict_types=1);

namespace Modules\Commerce\Orders\Services;

use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Illuminate\Validation\ValidationException;
use Modules\Commerce\Catalog\Models\ProductDigitalAsset;
use Modules\Commerce\Catalog\Services\DigitalAssetStorage;
use Modules\Commerce\Orders\Models\Order;
use Modules\Commerce\Orders\Models\OrderItem;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class DigitalFulfillmentService
{
    public const DOWNLOAD_URL_TTL_HOURS = 72;

    /**
     * @return array{item: OrderItem, download_url: string, expires_at: string, downloads_remaining: int|null}
     *
     * @throws ModelNotFoundException
     * @throws ValidationException
     */
    public function issueDownload(
        string $tenantId,
        string $orderNumber,
        string $customerEmail,
        string $orderItemId,
    ): array {
        $order = Order::query()
            ->where('tenant_id', $tenantId)
            ->where('order_number', $orderNumber)
            ->whereRaw('LOWER(customer_email) = ?', [strtolower($customerEmail)])
            ->firstOrFail();

        if (! in_array($order->status, [Order::STATUS_PAID, Order::STATUS_FULFILLED], true)) {
            throw ValidationException::withMessages([
                'order_number' => ['Order must be paid before downloading.'],
            ]);
        }

        $item = OrderItem::query()
            ->where('order_id', $order->id)
            ->where('id', $orderItemId)
            ->firstOrFail();

        if ($item->fulfillment_type !== 'digital') {
            throw ValidationException::withMessages([
                'order_item_id' => ['This order line is not a digital product.'],
            ]);
        }

        $limit = $item->download_limit;
        $count = (int) $item->download_count;

        if ($limit !== null && $count >= $limit) {
            throw ValidationException::withMessages([
                'order_item_id' => ['Download limit reached for this purchase.'],
            ]);
        }

        $asset = ProductDigitalAsset::query()
            ->where('tenant_id', $tenantId)
            ->where('product_id', $item->product_id)
            ->first();

        if ($asset === null || ! Storage::disk(DigitalAssetStorage::DISK)->exists($asset->storage_key)) {
            throw ValidationException::withMessages([
                'order_item_id' => ['Digital file is not available for this product.'],
            ]);
        }

        $expiresAt = now()->addHours(self::DOWNLOAD_URL_TTL_HOURS);
        $downloadUrl = URL::temporarySignedRoute(
            'orders.digital-downloads.file',
            $expiresAt,
            [
                'tenantId' => $tenantId,
                'orderItemId' => $item->id,
            ],
        );

        return [
            'item' => $item,
            'download_url' => $downloadUrl,
            'expires_at' => $expiresAt->toIso8601String(),
            'downloads_remaining' => $limit === null ? null : max(0, $limit - $count),
        ];
    }

    public function streamSignedDownload(string $tenantId, string $orderItemId): StreamedResponse
    {
        $item = OrderItem::query()
            ->where('id', $orderItemId)
            ->whereHas('order', static function ($query) use ($tenantId): void {
                $query->where('tenant_id', $tenantId);
            })
            ->firstOrFail();

        if ($item->fulfillment_type !== 'digital') {
            abort(404);
        }

        $limit = $item->download_limit;
        $count = (int) $item->download_count;

        if ($limit !== null && $count >= $limit) {
            abort(422, 'Download limit reached for this purchase.');
        }

        $asset = ProductDigitalAsset::query()
            ->where('tenant_id', $tenantId)
            ->where('product_id', $item->product_id)
            ->firstOrFail();

        $item->update([
            'download_count' => $count + 1,
            'downloaded_at' => $item->downloaded_at ?? now(),
        ]);

        return Storage::disk(DigitalAssetStorage::DISK)->download(
            $asset->storage_key,
            $asset->original_filename,
        );
    }

    /**
     * @deprecated Use issueDownload(); kept for call sites that only stamp downloaded_at.
     */
    public function recordDownload(
        string $tenantId,
        string $orderNumber,
        string $customerEmail,
        string $orderItemId,
    ): OrderItem {
        $result = $this->issueDownload($tenantId, $orderNumber, $customerEmail, $orderItemId);

        return $result['item'];
    }

    public function isDigitalDownloaded(OrderItem $item): bool
    {
        return $item->fulfillment_type === 'digital' && $item->downloaded_at !== null;
    }
}
