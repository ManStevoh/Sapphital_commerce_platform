<?php

declare(strict_types=1);

namespace Modules\Commerce\Orders\Services;

use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Validation\ValidationException;
use Modules\Commerce\Orders\Models\Order;
use Modules\Commerce\Orders\Models\OrderItem;

final class DigitalFulfillmentService
{
    /**
     * @throws ModelNotFoundException
     * @throws ValidationException
     */
    public function recordDownload(
        string $tenantId,
        string $orderNumber,
        string $customerEmail,
        string $orderItemId,
    ): OrderItem {
        $order = Order::query()
            ->where('tenant_id', $tenantId)
            ->where('order_number', $orderNumber)
            ->whereRaw('LOWER(customer_email) = ?', [strtolower($customerEmail)])
            ->firstOrFail();

        $item = OrderItem::query()
            ->where('order_id', $order->id)
            ->where('id', $orderItemId)
            ->firstOrFail();

        if ($item->fulfillment_type !== 'digital') {
            throw ValidationException::withMessages([
                'order_item_id' => ['This order line is not a digital product.'],
            ]);
        }

        if ($item->downloaded_at !== null) {
            return $item;
        }

        $item->update(['downloaded_at' => now()]);

        return $item->fresh();
    }

    public function isDigitalDownloaded(OrderItem $item): bool
    {
        return $item->fulfillment_type === 'digital' && $item->downloaded_at !== null;
    }
}
