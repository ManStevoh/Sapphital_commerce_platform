<?php

declare(strict_types=1);

namespace Tests\Feature\Notifications;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Modules\Commerce\Checkout\Models\CheckoutSession;
use Modules\Commerce\Orders\Models\Order;
use Modules\Commerce\Orders\Services\OrderService;
use Platform\Tenancy\Models\Tenant;
use Tests\Feature\PlatformTestCase;

final class OrderConfirmationNotifierTest extends PlatformTestCase
{
    public function test_mark_paid_logs_order_confirmation_in_testing(): void
    {
        Log::spy();

        $tenant = $this->createTenant();
        $order = Order::query()->create([
            'tenant_id' => $tenant->id,
            'checkout_session_id' => (string) Str::uuid(),
            'order_number' => 'ORD-'.Str::upper(Str::random(6)),
            'status' => Order::STATUS_PENDING,
            'currency' => 'NGN',
            'subtotal_kobo' => 1_000_000,
            'total_kobo' => 1_000_000,
            'customer_email' => 'buyer@example.com',
        ]);

        app(OrderService::class)->markPaid($order->id, 'pay_ref_123');

        Log::shouldHaveReceived('info')
            ->once()
            ->with('order.confirmation', \Mockery::subset([
                'order_id' => $order->id,
                'email' => 'buyer@example.com',
            ]));
    }

    public function test_order_created_from_checkout_copies_customer_email(): void
    {
        $tenant = $this->createTenant();

        $cart = \Modules\Commerce\Cart\Models\Cart::query()->create([
            'tenant_id' => $tenant->id,
            'session_id' => (string) Str::uuid(),
            'currency' => 'NGN',
        ]);

        $product = \Modules\Commerce\Catalog\Models\Product::query()->create([
            'tenant_id' => $tenant->id,
            'name' => 'Notifier Product',
            'slug' => 'notifier-product',
            'price_kobo' => 500_000,
            'status' => 'published',
            'inventory_qty' => 1,
        ]);

        \Modules\Commerce\Cart\Models\CartItem::query()->create([
            'cart_id' => $cart->id,
            'product_id' => $product->id,
            'quantity' => 1,
            'unit_price_kobo' => 500_000,
            'line_total_kobo' => 500_000,
        ]);

        $session = CheckoutSession::query()->create([
            'tenant_id' => $tenant->id,
            'cart_id' => $cart->id,
            'status' => CheckoutSession::STATUS_PENDING,
            'total_kobo' => 500_000,
            'customer_email' => 'checkout-buyer@example.com',
        ]);

        $order = app(OrderService::class)->createFromCheckoutSession($session);

        $this->assertSame('checkout-buyer@example.com', $order->customer_email);
    }

    private function createTenant(): Tenant
    {
        return Tenant::query()->create([
            'slug' => 'notify-'.Str::random(6),
            'name' => 'Notify Tenant',
            'status' => 'active',
            'country' => 'NG',
        ]);
    }
}
