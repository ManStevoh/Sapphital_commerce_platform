<?php

declare(strict_types=1);

namespace Modules\Commerce\Orders\Services;

use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Modules\Commerce\Orders\Enums\ReturnRequestStatus;
use Modules\Commerce\Orders\Models\Order;
use Modules\Commerce\Orders\Models\OrderItem;
use Modules\Commerce\Orders\Models\ReturnLine;
use Modules\Commerce\Orders\Models\ReturnRequest;
use Platform\FinancialServices\Services\RefundService;

final class ReturnRequestService
{
    public function __construct(
        private readonly RefundService $refundService,
        private readonly InventoryRestockService $inventoryRestock,
        private readonly ReturnWindowService $returnWindow,
        private readonly DigitalFulfillmentService $digitalFulfillment,
    ) {}

    /**
     * @param  list<array{order_item_id: string, quantity: int}>  $lines
     *
     * @throws ModelNotFoundException
     * @throws ValidationException
     */
    /**
     * @param  list<array{order_item_id: string, quantity: int, restock?: bool}>  $lines
     */
    public function create(
        string $tenantId,
        string $orderId,
        array $lines,
        string $reason,
        ?string $notes = null,
    ): ReturnRequest {
        return DB::transaction(function () use ($tenantId, $orderId, $lines, $reason, $notes): ReturnRequest {
            $order = Order::query()
                ->where('tenant_id', $tenantId)
                ->where('id', $orderId)
                ->with('items')
                ->firstOrFail();

            if (! in_array($order->status, [Order::STATUS_PAID, Order::STATUS_FULFILLED], true)) {
                throw ValidationException::withMessages([
                    'order_id' => ['Returns are only allowed for paid or fulfilled orders.'],
                ]);
            }

            $this->returnWindow->assertWithinWindow($order);

            if ($lines === []) {
                throw ValidationException::withMessages([
                    'lines' => ['At least one return line is required.'],
                ]);
            }

            $hasOpenReturn = ReturnRequest::query()
                ->where('tenant_id', $tenantId)
                ->where('order_id', $orderId)
                ->whereIn('status', [
                    ReturnRequestStatus::Requested,
                    ReturnRequestStatus::Approved,
                    ReturnRequestStatus::Shipped,
                    ReturnRequestStatus::Received,
                ])
                ->exists();

            if ($hasOpenReturn) {
                throw ValidationException::withMessages([
                    'order_id' => ['An open return request already exists for this order.'],
                ]);
            }

            $returnRequest = ReturnRequest::query()->create([
                'tenant_id' => $tenantId,
                'order_id' => $orderId,
                'status' => ReturnRequestStatus::Requested,
                'reason' => $reason,
                'notes' => $notes,
                'requested_at' => now(),
            ]);

            foreach ($lines as $line) {
                $this->attachLine(
                    $returnRequest,
                    $order,
                    $line['order_item_id'],
                    (int) $line['quantity'],
                    (bool) ($line['restock'] ?? true),
                );
            }

            return $returnRequest->fresh(['lines.orderItem']);
        });
    }

    /**
     * @throws ModelNotFoundException
     * @throws ValidationException
     */
    public function approve(string $tenantId, string $returnRequestId, bool $issueRefund = false): ReturnRequest
    {
        return DB::transaction(function () use ($tenantId, $returnRequestId, $issueRefund): ReturnRequest {
            $returnRequest = $this->findTenantReturn($tenantId, $returnRequestId);

            if ($returnRequest->status !== ReturnRequestStatus::Requested) {
                throw ValidationException::withMessages([
                    'return_request' => ['Only requested returns can be approved.'],
                ]);
            }

            $returnRequest->update([
                'status' => ReturnRequestStatus::Approved,
            ]);

            if ($issueRefund) {
                $this->completeRefundAndRestock($returnRequest);
            }

            return $returnRequest->fresh(['lines.orderItem', 'order']);
        });
    }

    /**
     * @throws ModelNotFoundException
     * @throws ValidationException
     */
    public function markShipped(string $tenantId, string $returnRequestId): ReturnRequest
    {
        $returnRequest = $this->findTenantReturn($tenantId, $returnRequestId);

        if ($returnRequest->status !== ReturnRequestStatus::Approved) {
            throw ValidationException::withMessages([
                'return_request' => ['Only approved returns can be marked as shipped.'],
            ]);
        }

        $returnRequest->update([
            'status' => ReturnRequestStatus::Shipped,
        ]);

        return $returnRequest->fresh(['lines.orderItem', 'order']);
    }

    /**
     * @throws ModelNotFoundException
     * @throws ValidationException
     */
    public function markReceived(string $tenantId, string $returnRequestId): ReturnRequest
    {
        return DB::transaction(function () use ($tenantId, $returnRequestId): ReturnRequest {
            $returnRequest = $this->findTenantReturn($tenantId, $returnRequestId);

            if ($returnRequest->status !== ReturnRequestStatus::Shipped) {
                throw ValidationException::withMessages([
                    'return_request' => ['Only shipped returns can be marked as received.'],
                ]);
            }

            $returnRequest->update([
                'status' => ReturnRequestStatus::Received,
            ]);

            $this->completeRefundAndRestock($returnRequest);

            return $returnRequest->fresh(['lines.orderItem', 'order']);
        });
    }

    /**
     * @throws ModelNotFoundException
     * @throws ValidationException
     */
    /**
     * @param  list<array{order_item_id: string, quantity: int, restock?: bool}>  $lines
     */
    public function createGuestReturn(
        string $tenantId,
        string $orderNumber,
        string $customerEmail,
        array $lines,
        string $reason,
        ?string $notes = null,
    ): ReturnRequest {
        $order = Order::query()
            ->where('tenant_id', $tenantId)
            ->where('order_number', $orderNumber)
            ->whereRaw('LOWER(customer_email) = ?', [strtolower($customerEmail)])
            ->first();

        if ($order === null) {
            throw ValidationException::withMessages([
                'order' => ['Order not found for the provided details.'],
            ]);
        }

        return $this->create($tenantId, $order->id, $lines, $reason, $notes);
    }

    /**
     * @return array{order_id: string, order_number: string, items: list<array{id: string, product_name: string, quantity: int}>}
     *
     * @throws ValidationException
     */
    public function lookupGuestOrder(
        string $tenantId,
        string $orderNumber,
        string $customerEmail,
    ): array {
        $order = Order::query()
            ->where('tenant_id', $tenantId)
            ->where('order_number', $orderNumber)
            ->whereRaw('LOWER(customer_email) = ?', [strtolower($customerEmail)])
            ->with('items')
            ->first();

        if ($order === null) {
            throw ValidationException::withMessages([
                'order' => ['Order not found for the provided details.'],
            ]);
        }

        if (! in_array($order->status, [Order::STATUS_PAID, Order::STATUS_FULFILLED], true)) {
            throw ValidationException::withMessages([
                'order' => ['Returns are only allowed for paid or fulfilled orders.'],
            ]);
        }

        return [
            'order_id' => $order->id,
            'order_number' => $order->order_number,
            'items' => $order->items->map(static fn (OrderItem $item): array => [
                'id' => $item->id,
                'product_name' => $item->product_name,
                'quantity' => $item->quantity,
            ])->values()->all(),
        ];
    }

    public function reject(string $tenantId, string $returnRequestId, string $rejectionReason): ReturnRequest
    {
        $returnRequest = $this->findTenantReturn($tenantId, $returnRequestId);

        if ($returnRequest->status !== ReturnRequestStatus::Requested) {
            throw ValidationException::withMessages([
                'return_request' => ['Only requested returns can be rejected.'],
            ]);
        }

        $returnRequest->update([
            'status' => ReturnRequestStatus::Rejected,
            'rejection_reason' => $rejectionReason,
            'resolved_at' => now(),
        ]);

        return $returnRequest->fresh(['lines.orderItem', 'order']);
    }

    private function attachLine(
        ReturnRequest $returnRequest,
        Order $order,
        string $orderItemId,
        int $quantity,
        bool $restock = true,
    ): void {
        /** @var OrderItem|null $orderItem */
        $orderItem = $order->items->firstWhere('id', $orderItemId);

        if ($orderItem === null) {
            throw ValidationException::withMessages([
                'lines' => ["Order item {$orderItemId} does not belong to this order."],
            ]);
        }

        if ($quantity < 1 || $quantity > $orderItem->quantity) {
            throw ValidationException::withMessages([
                'lines' => ["Invalid return quantity for item {$orderItemId}."],
            ]);
        }

        if ($this->digitalFulfillment->isDigitalDownloaded($orderItem)) {
            throw ValidationException::withMessages([
                'lines' => ['Digital products cannot be returned after download.'],
            ]);
        }

        ReturnLine::query()->create([
            'return_request_id' => $returnRequest->id,
            'order_item_id' => $orderItem->id,
            'quantity' => $quantity,
            'restock' => $restock,
        ]);
    }

    private function issueRefundForReturn(ReturnRequest $returnRequest): void
    {
        $returnRequest->loadMissing(['lines.orderItem', 'order']);

        $refundAmount = 0;

        foreach ($returnRequest->lines as $line) {
            $orderItem = $line->orderItem;

            if ($orderItem === null) {
                continue;
            }

            $refundAmount += (int) round(
                ($orderItem->line_total_kobo / max(1, $orderItem->quantity)) * $line->quantity,
            );
        }

        if ($refundAmount <= 0) {
            throw ValidationException::withMessages([
                'return_request' => ['Return has no refundable amount.'],
            ]);
        }

        $result = $this->refundService->refundOrder(
            $returnRequest->tenant_id,
            $returnRequest->order_id,
            $refundAmount,
            'Return '.$returnRequest->id,
        );

        unset($result);
    }

    private function completeRefundAndRestock(ReturnRequest $returnRequest): void
    {
        $this->issueRefundForReturn($returnRequest);
        $this->inventoryRestock->restockApprovedReturn($returnRequest->fresh(['lines.orderItem']));

        $returnRequest->update([
            'status' => ReturnRequestStatus::Refunded,
            'resolved_at' => now(),
        ]);
    }

    private function findTenantReturn(string $tenantId, string $returnRequestId): ReturnRequest
    {
        return ReturnRequest::query()
            ->where('tenant_id', $tenantId)
            ->where('id', $returnRequestId)
            ->with(['lines.orderItem', 'order'])
            ->firstOrFail();
    }
}
