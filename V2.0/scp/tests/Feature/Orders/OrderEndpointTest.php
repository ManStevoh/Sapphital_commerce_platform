<?php

declare(strict_types=1);

namespace Tests\Feature\Orders;

use Illuminate\Support\Str;
use Modules\Commerce\Cart\Models\Cart;
use Modules\Commerce\Cart\Models\CartItem;
use Modules\Commerce\Catalog\Models\Product;
use Modules\Commerce\Checkout\Models\CheckoutSession;
use Modules\Commerce\Orders\Models\Order;
use Modules\Commerce\Orders\Models\OrderItem;
use Platform\Tenancy\Models\Tenant;
use Tests\Feature\PlatformTestCase;

final class OrderEndpointTest extends PlatformTestCase
{
    public function test_create_order_from_checkout_session_with_items(): void
    {
        $tenant = $this->createTenant();
        $session = $this->createCheckoutSession($tenant, 3_000_000);

        $response = $this->postJson('/api/v1/commerce/orders/from-checkout', [
            'checkout_session_id' => $session->id,
        ], [
            'X-Tenant-ID' => $tenant->id,
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.status', 'pending')
            ->assertJsonPath('data.currency', 'NGN')
            ->assertJsonPath('data.subtotal_kobo', 3_000_000)
            ->assertJsonPath('data.total_kobo', 3_000_000)
            ->assertJsonPath('data.checkout_session_id', $session->id)
            ->assertJsonCount(1, 'data.items')
            ->assertJsonPath('data.items.0.product_name', 'Order Product')
            ->assertJsonPath('data.items.0.quantity', 1)
            ->assertJsonPath('data.items.0.unit_price_kobo', 3_000_000)
            ->assertJsonPath('data.items.0.line_total_kobo', 3_000_000);

        $orderId = $response->json('data.id');

        $this->assertNotNull($orderId);
        $this->assertDatabaseHas('orders', [
            'id' => $orderId,
            'tenant_id' => $tenant->id,
            'checkout_session_id' => $session->id,
            'status' => 'pending',
            'subtotal_kobo' => 3_000_000,
            'total_kobo' => 3_000_000,
        ]);

        $this->assertDatabaseHas('order_items', [
            'order_id' => $orderId,
            'quantity' => 1,
            'unit_price_kobo' => 3_000_000,
            'line_total_kobo' => 3_000_000,
            'product_name' => 'Order Product',
        ]);

        $this->assertDatabaseHas('checkout_sessions', [
            'id' => $session->id,
            'status' => 'completed',
        ]);
    }

    public function test_list_orders_is_tenant_scoped(): void
    {
        $tenantA = $this->createTenant('tenant-a');
        $tenantB = $this->createTenant('tenant-b');
        $token = $this->createMerchantForTenant($tenantA)->createToken('test')->plainTextToken;

        $orderA = $this->createOrderForTenant($tenantA, 'ORD-A-001');
        $this->createOrderForTenant($tenantB, 'ORD-B-001');

        $response = $this->getJson('/api/v1/commerce/orders', $this->merchantAuthHeaders($tenantA->id, $token));

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $orderA->id)
            ->assertJsonPath('data.0.order_number', 'ORD-A-001');
    }

    public function test_show_order_returns_404_for_other_tenant(): void
    {
        $tenant = $this->createTenant();
        $otherTenant = $this->createTenant('other');
        $order = $this->createOrderForTenant($otherTenant, 'ORD-OTHER-001');

        $response = $this->getJson('/api/v1/commerce/orders/'.$order->id, [
            'X-Tenant-ID' => $tenant->id,
        ]);

        $response->assertNotFound()
            ->assertJson([
                'message' => 'Order not found.',
            ]);
    }

    private function createTenant(string $prefix = 'orders'): Tenant
    {
        return Tenant::query()->create([
            'slug' => $prefix.'-'.Str::random(8),
            'name' => ucfirst($prefix).' Tenant',
            'status' => 'active',
            'country' => 'NG',
        ]);
    }

    private function createCheckoutSession(Tenant $tenant, int $lineTotalKobo): CheckoutSession
    {
        $product = Product::query()->create([
            'tenant_id' => $tenant->id,
            'name' => 'Order Product',
            'slug' => 'order-product-'.Str::random(6),
            'price_kobo' => $lineTotalKobo,
            'status' => 'published',
            'inventory_qty' => 5,
        ]);

        $cart = Cart::query()->create([
            'tenant_id' => $tenant->id,
            'session_id' => (string) Str::uuid(),
            'currency' => 'NGN',
        ]);

        CartItem::query()->create([
            'cart_id' => $cart->id,
            'product_id' => $product->id,
            'quantity' => 1,
            'unit_price_kobo' => $lineTotalKobo,
            'line_total_kobo' => $lineTotalKobo,
        ]);

        return CheckoutSession::query()->create([
            'tenant_id' => $tenant->id,
            'cart_id' => $cart->id,
            'status' => CheckoutSession::STATUS_PENDING,
            'total_kobo' => $lineTotalKobo,
        ]);
    }

    private function createOrderForTenant(Tenant $tenant, string $orderNumber): Order
    {
        $order = Order::query()->create([
            'tenant_id' => $tenant->id,
            'checkout_session_id' => null,
            'order_number' => $orderNumber,
            'status' => Order::STATUS_PENDING,
            'currency' => 'NGN',
            'subtotal_kobo' => 1_000,
            'total_kobo' => 1_000,
        ]);

        OrderItem::query()->create([
            'order_id' => $order->id,
            'product_id' => (string) Str::uuid(),
            'product_name' => 'Listed Product',
            'quantity' => 1,
            'unit_price_kobo' => 1_000,
            'line_total_kobo' => 1_000,
        ]);

        return $order;
    }
}
