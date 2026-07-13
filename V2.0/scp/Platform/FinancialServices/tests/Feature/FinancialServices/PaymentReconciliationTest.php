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

final class PaymentReconciliationTest extends PlatformTestCase
{
    public function test_finance_user_can_view_reconciliation_report(): void
    {
        $tenant = $this->createTenant();
        $merchant = $this->createMerchantForTenant(
            $tenant,
            'finance-recon@test.com',
            'password12345',
            MerchantUserRole::Finance,
        );
        $this->createActiveSubscription($tenant->id);

        $order = Order::query()->create([
            'tenant_id' => $tenant->id,
            'checkout_session_id' => null,
            'order_number' => 'ORD-REC-'.Str::upper(Str::random(4)),
            'status' => Order::STATUS_PAID,
            'currency' => 'NGN',
            'subtotal_kobo' => 250_000,
            'total_kobo' => 250_000,
            'customer_email' => 'buyer@test.com',
            'paystack_reference' => 'pay_rec_'.Str::random(8),
            'created_at' => now()->subDays(2),
        ]);

        Refund::query()->create([
            'tenant_id' => $tenant->id,
            'order_id' => $order->id,
            'amount_kobo' => 50_000,
            'currency' => 'NGN',
            'status' => RefundStatus::Completed,
            'reason' => 'Partial return',
            'paystack_reference' => (string) $order->paystack_reference,
            'gateway_refund_reference' => 'rf_ref_'.Str::random(6),
            'processed_at' => now()->subDay(),
        ]);

        $token = $this->loginMerchant($merchant->email, 'password12345');

        $response = $this->getJson(
            '/api/v1/platform/financial-services/reconciliation?from='.now()->subDays(7)->toDateString().'&to='.now()->toDateString(),
            $this->merchantAuthHeaders($tenant->id, $token),
        );

        $response->assertOk()
            ->assertJsonPath('data.summary.charge_count', 1)
            ->assertJsonPath('data.summary.refund_count', 1)
            ->assertJsonPath('data.summary.total_charges_kobo', 250_000)
            ->assertJsonPath('data.summary.total_refunds_kobo', 50_000)
            ->assertJsonPath('data.summary.net_kobo', 200_000)
            ->assertJsonCount(2, 'data.entries');
    }

    public function test_staff_user_cannot_view_reconciliation_report(): void
    {
        $tenant = $this->createTenant();
        $merchant = $this->createMerchantForTenant(
            $tenant,
            'staff-recon@test.com',
            'password12345',
            MerchantUserRole::Staff,
        );
        $this->createActiveSubscription($tenant->id);

        $token = $this->loginMerchant($merchant->email, 'password12345');

        $this->getJson(
            '/api/v1/platform/financial-services/reconciliation',
            $this->merchantAuthHeaders($tenant->id, $token),
        )->assertForbidden();
    }

    public function test_finance_user_can_export_reconciliation_csv(): void
    {
        $tenant = $this->createTenant();
        $merchant = $this->createMerchantForTenant(
            $tenant,
            'finance-export@test.com',
            'password12345',
            MerchantUserRole::Finance,
        );
        $this->createActiveSubscription($tenant->id);

        Order::query()->create([
            'tenant_id' => $tenant->id,
            'checkout_session_id' => null,
            'order_number' => 'ORD-CSV-'.Str::upper(Str::random(4)),
            'status' => Order::STATUS_PAID,
            'currency' => 'NGN',
            'subtotal_kobo' => 100_000,
            'total_kobo' => 100_000,
            'paystack_reference' => 'pay_csv_'.Str::random(8),
            'created_at' => now()->subDay(),
        ]);

        $token = $this->loginMerchant($merchant->email, 'password12345');

        $response = $this->get(
            '/api/v1/platform/financial-services/reconciliation/export?from='.now()->subDays(3)->toDateString().'&to='.now()->toDateString(),
            $this->merchantAuthHeaders($tenant->id, $token),
        );

        $response->assertOk();
        $this->assertStringContainsString('text/csv', (string) $response->headers->get('content-type'));
        $content = $response->streamedContent();
        $this->assertStringContainsString('type,occurred_at,order_number', $content);
        $this->assertStringContainsString('net_kobo,100000', $content);
    }

    private function createTenant(): Tenant
    {
        return Tenant::query()->create([
            'slug' => 'recon-'.Str::random(6),
            'name' => 'Reconciliation Tenant',
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
