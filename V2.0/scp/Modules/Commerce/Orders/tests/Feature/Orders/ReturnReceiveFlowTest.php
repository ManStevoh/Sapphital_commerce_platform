<?php

declare(strict_types=1);

namespace Modules\Commerce\Orders\Tests\Feature\Orders;

use Illuminate\Support\Str;
use Modules\Commerce\Orders\Models\Order;
use Modules\Commerce\Orders\Models\OrderItem;
use Platform\Billing\Enums\SubscriptionStatus;
use Platform\Billing\Models\Plan;
use Platform\Billing\Models\Subscription;
use Platform\Tenancy\Models\Tenant;
use Tests\Feature\PlatformTestCase;

final class ReturnReceiveFlowTest extends PlatformTestCase
{
    public function test_physical_return_flow_approve_ship_receive_then_refund(): void
    {
        $tenant = $this->createTenant();
        $merchant = $this->createMerchantForTenant($tenant, 'receive@test.com', 'password12345');
        $this->createActiveSubscription($tenant->id);

        [$order, $orderItem] = $this->createPaidOrderWithItem($tenant);
        $token = $this->loginMerchant($merchant->email, 'password12345');
        $headers = $this->merchantAuthHeaders($tenant->id, $token);

        $create = $this->postJson('/api/v1/commerce/returns', [
            'order_id' => $order->id,
            'reason' => 'defective',
            'lines' => [
                ['order_item_id' => $orderItem->id, 'quantity' => 1, 'restock' => true],
            ],
        ], $headers);

        $returnId = (string) $create->json('data.id');

        $this->postJson("/api/v1/commerce/returns/{$returnId}/approve", [], $headers)
            ->assertOk()
            ->assertJsonPath('data.status', 'approved');

        $this->assertDatabaseMissing('refunds', ['order_id' => $order->id]);

        $this->postJson("/api/v1/commerce/returns/{$returnId}/ship", [], $headers)
            ->assertOk()
            ->assertJsonPath('data.status', 'shipped');

        $this->postJson(
            "/api/v1/commerce/returns/{$returnId}/receive",
            [],
            array_merge($headers, $this->idempotencyHeaders()),
        )->assertOk()
            ->assertJsonPath('data.status', 'refunded');

        $this->assertDatabaseHas('refunds', [
            'order_id' => $order->id,
            'amount_kobo' => 500_000,
        ]);

        $order->refresh();
        $this->assertSame(Order::STATUS_REFUNDED, $order->status);
    }

    /**
     * @return array{0: Order, 1: OrderItem}
     */
    private function createPaidOrderWithItem(Tenant $tenant): array
    {
        $product = \Modules\Commerce\Catalog\Models\Product::query()->create([
            'tenant_id' => $tenant->id,
            'name' => 'Receive Product',
            'slug' => 'receive-product-'.Str::random(4),
            'price_kobo' => 500_000,
            'status' => 'published',
            'inventory_qty' => 0,
        ]);

        $order = Order::query()->create([
            'tenant_id' => $tenant->id,
            'checkout_session_id' => null,
            'order_number' => 'ORD-RCV-'.Str::upper(Str::random(4)),
            'status' => Order::STATUS_PAID,
            'currency' => 'NGN',
            'subtotal_kobo' => 500_000,
            'total_kobo' => 500_000,
            'paystack_reference' => 'pay_ref_'.Str::random(8),
        ]);

        $item = OrderItem::query()->create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'product_name' => $product->name,
            'quantity' => 1,
            'unit_price_kobo' => 500_000,
            'line_total_kobo' => 500_000,
        ]);

        return [$order, $item];
    }

    private function createTenant(): Tenant
    {
        return Tenant::query()->create([
            'slug' => 'receive-'.Str::random(6),
            'name' => 'Receive Tenant',
            'status' => 'active',
            'country' => 'NG',
        ]);
    }

    private function createActiveSubscription(string $tenantId): void
    {
        $plan = Plan::query()->where('slug', 'starter')->firstOrFail();

        Subscription::query()->create([
            'tenant_id' => $tenantId,
            'plan_id' => $plan->id,
            'status' => SubscriptionStatus::Active,
        ]);
    }

    private function loginMerchant(string $email, string $password): string
    {
        $login = $this->postJson('/api/v1/auth/merchant/login', [
            'email' => $email,
            'password' => $password,
        ]);

        return (string) $login->json('token');
    }
}
