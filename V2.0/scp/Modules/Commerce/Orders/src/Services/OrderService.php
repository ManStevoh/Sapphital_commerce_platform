<?php

declare(strict_types=1);

namespace Modules\Commerce\Orders\Services;

use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Modules\Commerce\Cart\Models\Cart;
use Modules\Commerce\Catalog\Models\Product;
use Modules\Commerce\Checkout\Models\CheckoutSession;
use Modules\Commerce\Orders\Models\Order;
use Modules\Commerce\Orders\Models\OrderItem;

final class OrderService
{
    /**
     * @throws ValidationException
     */
    public function createFromCheckoutSession(CheckoutSession $session): Order
    {
        if ($session->status !== CheckoutSession::STATUS_PENDING) {
            throw ValidationException::withMessages([
                'checkout_session_id' => ['Checkout session is not available for order creation.'],
            ]);
        }

        $cart = Cart::query()
            ->where('id', $session->cart_id)
            ->where('tenant_id', $session->tenant_id)
            ->with('items')
            ->firstOrFail();

        if ($cart->items->isEmpty()) {
            throw ValidationException::withMessages([
                'checkout_session_id' => ['Checkout cart has no items.'],
            ]);
        }

        $productNames = Product::query()
            ->where('tenant_id', $session->tenant_id)
            ->whereIn('id', $cart->items->pluck('product_id'))
            ->pluck('name', 'id');

        $subtotalKobo = (int) $cart->items->sum('line_total_kobo');

        return DB::transaction(function () use ($session, $cart, $productNames, $subtotalKobo): Order {
            $order = Order::query()->create([
                'tenant_id' => $session->tenant_id,
                'checkout_session_id' => $session->id,
                'order_number' => $this->generateOrderNumber($session->tenant_id),
                'status' => Order::STATUS_PENDING,
                'currency' => 'NGN',
                'subtotal_kobo' => $subtotalKobo,
                'total_kobo' => $subtotalKobo,
                'customer_email' => null,
                'paystack_reference' => null,
            ]);

            foreach ($cart->items as $cartItem) {
                OrderItem::query()->create([
                    'order_id' => $order->id,
                    'product_id' => $cartItem->product_id,
                    'product_name' => (string) ($productNames[$cartItem->product_id] ?? 'Unknown Product'),
                    'quantity' => $cartItem->quantity,
                    'unit_price_kobo' => $cartItem->unit_price_kobo,
                    'line_total_kobo' => $cartItem->line_total_kobo,
                ]);
            }

            $session->update([
                'status' => CheckoutSession::STATUS_COMPLETED,
            ]);

            return $order->load('items');
        });
    }

    /**
     * @throws ModelNotFoundException
     */
    public function markPaid(string $orderId, string $paystackReference): Order
    {
        $order = Order::query()->findOrFail($orderId);

        $order->update([
            'status' => Order::STATUS_PAID,
            'paystack_reference' => $paystackReference,
        ]);

        $order = $order->fresh(['items']);

        $this->maybeCreateShipmentFromOrder($order);

        return $order;
    }

    private function maybeCreateShipmentFromOrder(Order $order): void
    {
        $shipmentServiceClass = 'Modules\\Commerce\\Shipping\\Services\\ShipmentService';

        if (! class_exists($shipmentServiceClass)) {
            return;
        }

        try {
            /** @var object $shipmentService */
            $shipmentService = app($shipmentServiceClass);

            if (method_exists($shipmentService, 'createFromOrder')) {
                $shipmentService->createFromOrder($order);
            }
        } catch (\Throwable) {
            // Shipping module unavailable — continue without auto-shipment.
        }
    }

    private function generateOrderNumber(string $tenantId): string
    {
        do {
            $orderNumber = 'ORD-'.now()->format('Ymd').'-'.Str::upper(Str::random(6));
        } while (
            Order::query()
                ->where('tenant_id', $tenantId)
                ->where('order_number', $orderNumber)
                ->exists()
        );

        return $orderNumber;
    }
}
