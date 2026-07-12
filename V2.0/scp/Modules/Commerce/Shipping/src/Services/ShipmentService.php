<?php

declare(strict_types=1);

namespace Modules\Commerce\Shipping\Services;

use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Modules\Commerce\Orders\Models\Order;
use Modules\Commerce\Shipping\Models\Shipment;
use Modules\Commerce\Shipping\Models\ShipmentLine;

final class ShipmentService
{
    /**
     * @throws ValidationException
     */
    public function createFromOrder(Order $order): Shipment
    {
        $order->loadMissing('items');

        if ($order->items->isEmpty()) {
            throw ValidationException::withMessages([
                'order_id' => ['Order has no items to ship.'],
            ]);
        }

        return DB::transaction(function () use ($order): Shipment {
            $shipment = Shipment::query()->create([
                'tenant_id' => $order->tenant_id,
                'order_id' => $order->id,
                'status' => Shipment::STATUS_PENDING,
                'carrier' => Shipment::CARRIER_MANUAL,
            ]);

            foreach ($order->items as $orderItem) {
                ShipmentLine::query()->create([
                    'shipment_id' => $shipment->id,
                    'order_item_id' => $orderItem->id,
                    'quantity' => $orderItem->quantity,
                ]);
            }

            return $shipment->load('lines');
        });
    }

    /**
     * @throws ModelNotFoundException
     * @throws ValidationException
     */
    public function markShipped(string $shipmentId, string $trackingNumber, ?string $trackingUrl = null): Shipment
    {
        $shipment = Shipment::query()->findOrFail($shipmentId);

        if ($shipment->status === Shipment::STATUS_DELIVERED) {
            throw ValidationException::withMessages([
                'shipment_id' => ['Shipment has already been delivered.'],
            ]);
        }

        $shipment->update([
            'status' => Shipment::STATUS_IN_TRANSIT,
            'tracking_number' => $trackingNumber,
            'tracking_url' => $trackingUrl,
            'shipped_at' => now(),
        ]);

        return $shipment->fresh(['lines']);
    }

    /**
     * @throws ModelNotFoundException
     * @throws ValidationException
     */
    public function markDelivered(string $shipmentId): Shipment
    {
        $shipment = Shipment::query()->findOrFail($shipmentId);

        if ($shipment->status === Shipment::STATUS_DELIVERED) {
            return $shipment->load('lines');
        }

        return DB::transaction(function () use ($shipment): Shipment {
            $shipment->update([
                'status' => Shipment::STATUS_DELIVERED,
                'delivered_at' => now(),
            ]);

            $this->fulfillOrderIfAllItemsDelivered($shipment->order_id);

            return $shipment->fresh(['lines']);
        });
    }

    private function fulfillOrderIfAllItemsDelivered(string $orderId): void
    {
        $order = Order::query()->with('items')->find($orderId);

        if ($order === null || $order->status === Order::STATUS_FULFILLED) {
            return;
        }

        $deliveredQuantities = ShipmentLine::query()
            ->whereIn('shipment_id', Shipment::query()
                ->where('order_id', $orderId)
                ->where('status', Shipment::STATUS_DELIVERED)
                ->pluck('id'))
            ->selectRaw('order_item_id, SUM(quantity) as total_quantity')
            ->groupBy('order_item_id')
            ->pluck('total_quantity', 'order_item_id');

        foreach ($order->items as $orderItem) {
            $deliveredQty = (int) ($deliveredQuantities[$orderItem->id] ?? 0);

            if ($deliveredQty < $orderItem->quantity) {
                return;
            }
        }

        $order->update([
            'status' => Order::STATUS_FULFILLED,
        ]);
    }
}
