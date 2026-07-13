<?php

declare(strict_types=1);

namespace Platform\FinancialServices\Tests\Feature\FinancialServices;

use Illuminate\Support\Str;
use Modules\Commerce\Orders\Models\Order;
use Platform\Billing\Enums\SubscriptionStatus;
use Platform\Billing\Models\Plan;
use Platform\Billing\Models\Subscription;
use Platform\FinancialServices\Enums\RefundStatus;
use Platform\FinancialServices\Models\Refund;
use Platform\Identity\Enums\MerchantUserRole;
use Platform\Tenancy\Models\Tenant;
use Tests\Feature\PlatformTestCase;

final class RefundFlowTest extends PlatformTestCase
{
    public function test_merchant_can_refund_paid_order(): void
    {
        $tenant = $this->createTenant();
        $merchant = $this->createMerchantForTenant($tenant, 'refund@test.com', 'password12345');
        $this->createActiveSubscription($tenant->id);

        $order = Order::query()->create([
            'tenant_id' => $tenant->id,
            'checkout_session_id' => null,
            'order_number' => 'ORD-REF-'.Str::upper(Str::random(4)),
            'status' => Order::STATUS_PAID,
            'currency' => 'NGN',
            'subtotal_kobo' => 500_000,
            'total_kobo' => 500_000,
            'paystack_reference' => 'pay_ref_'.Str::random(8),
        ]);

        $token = $this->loginMerchant($merchant->email, 'password12345');

        $response = $this->postJson(
            "/api/v1/commerce/orders/{$order->id}/refund",
            ['reason' => 'Customer return'],
            array_merge(
                $this->merchantAuthHeaders($tenant->id, $token),
                $this->idempotencyHeaders(),
            ),
        );

        $response->assertOk()
            ->assertJsonPath('data.refund.status', 'completed')
            ->assertJsonPath('data.refund.amount_kobo', 500_000)
            ->assertJsonPath('data.order.status', Order::STATUS_REFUNDED);

        $this->assertDatabaseHas('refunds', [
            'order_id' => $order->id,
            'status' => RefundStatus::Completed->value,
            'amount_kobo' => 500_000,
        ]);
    }

    public function test_partial_refund_does_not_mark_order_refunded(): void
    {
        $tenant = $this->createTenant();
        $merchant = $this->createMerchantForTenant($tenant, 'partial@test.com', 'password12345');
        $this->createActiveSubscription($tenant->id);

        $order = Order::query()->create([
            'tenant_id' => $tenant->id,
            'checkout_session_id' => null,
            'order_number' => 'ORD-PART-'.Str::upper(Str::random(4)),
            'status' => Order::STATUS_PAID,
            'currency' => 'NGN',
            'subtotal_kobo' => 1_000_000,
            'total_kobo' => 1_000_000,
            'paystack_reference' => 'pay_ref_'.Str::random(8),
        ]);

        $token = $this->loginMerchant($merchant->email, 'password12345');

        $this->postJson(
            "/api/v1/commerce/orders/{$order->id}/refund",
            ['amount_kobo' => 300_000],
            array_merge(
                $this->merchantAuthHeaders($tenant->id, $token),
                $this->idempotencyHeaders(),
            ),
        )->assertOk()
            ->assertJsonPath('data.order.status', Order::STATUS_PAID);

        $this->assertSame(1, Refund::query()->where('order_id', $order->id)->count());
    }

    public function test_staff_cannot_refund_order(): void
    {
        $tenant = $this->createTenant();
        $staff = $this->createMerchantForTenant(
            $tenant,
            'staff@test.com',
            'password12345',
            MerchantUserRole::Staff,
        );
        $this->createActiveSubscription($tenant->id);

        $order = Order::query()->create([
            'tenant_id' => $tenant->id,
            'checkout_session_id' => null,
            'order_number' => 'ORD-DENY-'.Str::upper(Str::random(4)),
            'status' => Order::STATUS_PAID,
            'currency' => 'NGN',
            'subtotal_kobo' => 100_000,
            'total_kobo' => 100_000,
            'paystack_reference' => 'pay_ref_'.Str::random(8),
        ]);

        $token = $this->loginMerchant($staff->email, 'password12345');

        $this->postJson(
            "/api/v1/commerce/orders/{$order->id}/refund",
            [],
            array_merge(
                $this->merchantAuthHeaders($tenant->id, $token),
                $this->idempotencyHeaders(),
            ),
        )->assertForbidden();
    }

    private function createTenant(): Tenant
    {
        return Tenant::query()->create([
            'slug' => 'refund-'.Str::random(6),
            'name' => 'Refund Tenant',
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
