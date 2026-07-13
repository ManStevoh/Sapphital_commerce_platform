<?php

declare(strict_types=1);

namespace Modules\Commerce\Orders\Tests\Feature\Orders;

use Illuminate\Support\Str;
use Modules\Commerce\Catalog\Models\Product;
use Modules\Commerce\Orders\Models\Order;
use Modules\Commerce\Orders\Models\OrderItem;
use Platform\Billing\Enums\SubscriptionStatus;
use Platform\Billing\Models\Plan;
use Platform\Billing\Models\Subscription;
use Platform\Tenancy\Models\Tenant;
use Tests\Feature\PlatformTestCase;

final class GuestReturnRequestTest extends PlatformTestCase
{
    public function test_guest_can_submit_return_with_order_number_and_email(): void
    {
        $tenant = $this->createTenant();
        [$order, $item] = $this->createPaidOrderWithItem($tenant, 'buyer@example.com');

        $lookup = $this->postJson('/api/v1/commerce/returns/guest/lookup', [
            'order_number' => $order->order_number,
            'customer_email' => 'buyer@example.com',
        ], [
            'X-Tenant-ID' => $tenant->id,
        ]);

        $lookup->assertOk()
            ->assertJsonPath('data.order_id', $order->id)
            ->assertJsonCount(1, 'data.items');

        $response = $this->postJson('/api/v1/commerce/returns/guest', [
            'order_number' => $order->order_number,
            'customer_email' => 'buyer@example.com',
            'reason' => 'defective',
            'lines' => [
                ['order_item_id' => $item->id, 'quantity' => 1],
            ],
        ], [
            'X-Tenant-ID' => $tenant->id,
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.status', 'requested');
    }

    public function test_guest_return_rejects_wrong_email(): void
    {
        $tenant = $this->createTenant();
        [$order, $item] = $this->createPaidOrderWithItem($tenant, 'buyer@example.com');

        $this->postJson('/api/v1/commerce/returns/guest', [
            'order_number' => $order->order_number,
            'customer_email' => 'wrong@example.com',
            'reason' => 'defective',
            'lines' => [
                ['order_item_id' => $item->id, 'quantity' => 1],
            ],
        ], [
            'X-Tenant-ID' => $tenant->id,
        ])->assertUnprocessable();
    }

    public function test_approve_return_restock_increments_inventory(): void
    {
        $tenant = $this->createTenant();
        $merchant = $this->createMerchantForTenant($tenant, 'restock@test.com', 'password12345');
        $this->createActiveSubscription($tenant->id);

        $product = Product::query()->create([
            'tenant_id' => $tenant->id,
            'name' => 'Restock Product',
            'slug' => 'restock-product',
            'price_kobo' => 500_000,
            'status' => 'published',
            'inventory_qty' => 0,
        ]);

        $order = Order::query()->create([
            'tenant_id' => $tenant->id,
            'checkout_session_id' => null,
            'order_number' => 'ORD-RST-'.Str::upper(Str::random(4)),
            'status' => Order::STATUS_PAID,
            'currency' => 'NGN',
            'subtotal_kobo' => 500_000,
            'total_kobo' => 500_000,
            'customer_email' => 'restock-buyer@example.com',
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

        $token = $this->loginMerchant($merchant->email, 'password12345');

        $create = $this->postJson(
            '/api/v1/commerce/returns',
            [
                'order_id' => $order->id,
                'reason' => 'defective',
                'lines' => [
                    ['order_item_id' => $item->id, 'quantity' => 1, 'restock' => true],
                ],
            ],
            $this->merchantAuthHeaders($tenant->id, $token),
        );

        $returnId = (string) $create->json('data.id');

        $this->postJson(
            "/api/v1/commerce/returns/{$returnId}/approve",
            ['issue_refund' => true],
            array_merge(
                $this->merchantAuthHeaders($tenant->id, $token),
                $this->idempotencyHeaders(),
            ),
        )->assertOk();

        $product->refresh();
        $this->assertSame(1, $product->inventory_qty);
    }

    /**
     * @return array{0: Order, 1: OrderItem}
     */
    private function createPaidOrderWithItem(Tenant $tenant, string $email): array
    {
        $product = Product::query()->create([
            'tenant_id' => $tenant->id,
            'name' => 'Guest Return Product',
            'slug' => 'guest-return-'.Str::random(4),
            'price_kobo' => 500_000,
            'status' => 'published',
            'inventory_qty' => 1,
        ]);

        $order = Order::query()->create([
            'tenant_id' => $tenant->id,
            'checkout_session_id' => null,
            'order_number' => 'ORD-GST-'.Str::upper(Str::random(4)),
            'status' => Order::STATUS_PAID,
            'currency' => 'NGN',
            'subtotal_kobo' => 500_000,
            'total_kobo' => 500_000,
            'customer_email' => $email,
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
            'slug' => 'guest-ret-'.Str::random(6),
            'name' => 'Guest Return Tenant',
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
