<?php

declare(strict_types=1);

namespace Modules\Commerce\Orders\Tests\Feature\Orders;

use Illuminate\Support\Str;
use Modules\Commerce\Orders\Enums\ReturnRequestStatus;
use Modules\Commerce\Orders\Models\Order;
use Modules\Commerce\Orders\Models\OrderItem;
use Modules\Commerce\Orders\Models\ReturnRequest;
use Platform\Billing\Enums\SubscriptionStatus;
use Platform\Billing\Models\Plan;
use Platform\Billing\Models\Subscription;
use Platform\Tenancy\Models\Tenant;
use Tests\Feature\PlatformTestCase;

final class ReturnRequestFlowTest extends PlatformTestCase
{
    public function test_merchant_can_create_and_approve_return_with_refund(): void
    {
        $tenant = $this->createTenant();
        $merchant = $this->createMerchantForTenant($tenant, 'returns@test.com', 'password12345');
        $this->createActiveSubscription($tenant->id);

        [$order, $orderItem] = $this->createPaidOrderWithItem($tenant);

        $token = $this->loginMerchant($merchant->email, 'password12345');

        $create = $this->postJson(
            '/api/v1/commerce/returns',
            [
                'order_id' => $order->id,
                'reason' => 'defective',
                'lines' => [
                    ['order_item_id' => $orderItem->id, 'quantity' => 1],
                ],
            ],
            $this->merchantAuthHeaders($tenant->id, $token),
        );

        $create->assertCreated()
            ->assertJsonPath('data.status', 'requested');

        $returnId = (string) $create->json('data.id');

        $approve = $this->postJson(
            "/api/v1/commerce/returns/{$returnId}/approve",
            ['issue_refund' => true],
            array_merge(
                $this->merchantAuthHeaders($tenant->id, $token),
                $this->idempotencyHeaders(),
            ),
        );

        $approve->assertOk()
            ->assertJsonPath('data.status', 'refunded');

        $this->assertDatabaseHas('refunds', [
            'order_id' => $order->id,
            'amount_kobo' => 500_000,
        ]);

        $order->refresh();
        $this->assertSame(Order::STATUS_REFUNDED, $order->status);
    }

    public function test_merchant_can_reject_return_request(): void
    {
        $tenant = $this->createTenant();
        $merchant = $this->createMerchantForTenant($tenant, 'reject@test.com', 'password12345');
        $this->createActiveSubscription($tenant->id);

        [$order, $orderItem] = $this->createPaidOrderWithItem($tenant);
        $token = $this->loginMerchant($merchant->email, 'password12345');

        $create = $this->postJson(
            '/api/v1/commerce/returns',
            [
                'order_id' => $order->id,
                'reason' => 'changed_mind',
                'lines' => [
                    ['order_item_id' => $orderItem->id, 'quantity' => 1],
                ],
            ],
            $this->merchantAuthHeaders($tenant->id, $token),
        );

        $returnId = (string) $create->json('data.id');

        $this->postJson(
            "/api/v1/commerce/returns/{$returnId}/reject",
            ['rejection_reason' => 'Outside return window'],
            $this->merchantAuthHeaders($tenant->id, $token),
        )->assertOk()
            ->assertJsonPath('data.status', 'rejected');

        $this->assertDatabaseMissing('refunds', [
            'order_id' => $order->id,
        ]);
    }

    public function test_duplicate_open_return_is_rejected(): void
    {
        $tenant = $this->createTenant();
        $merchant = $this->createMerchantForTenant($tenant, 'dup@test.com', 'password12345');
        $this->createActiveSubscription($tenant->id);

        [$order, $orderItem] = $this->createPaidOrderWithItem($tenant);
        $token = $this->loginMerchant($merchant->email, 'password12345');

        $payload = [
            'order_id' => $order->id,
            'reason' => 'defective',
            'lines' => [
                ['order_item_id' => $orderItem->id, 'quantity' => 1],
            ],
        ];

        $this->postJson('/api/v1/commerce/returns', $payload, $this->merchantAuthHeaders($tenant->id, $token))
            ->assertCreated();

        $this->postJson('/api/v1/commerce/returns', $payload, $this->merchantAuthHeaders($tenant->id, $token))
            ->assertUnprocessable();
    }

    /**
     * @return array{0: Order, 1: OrderItem}
     */
    private function createPaidOrderWithItem(Tenant $tenant): array
    {
        $product = \Modules\Commerce\Catalog\Models\Product::query()->create([
            'tenant_id' => $tenant->id,
            'name' => 'Return Product',
            'slug' => 'return-product-'.Str::random(4),
            'price_kobo' => 500_000,
            'status' => 'published',
            'inventory_qty' => 1,
        ]);

        $order = Order::query()->create([
            'tenant_id' => $tenant->id,
            'checkout_session_id' => null,
            'order_number' => 'ORD-RET-'.Str::upper(Str::random(4)),
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
            'slug' => 'returns-'.Str::random(6),
            'name' => 'Returns Tenant',
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

        $login->assertOk();

        return (string) $login->json('token');
    }
}
