<?php

declare(strict_types=1);

namespace Tests\Feature\Billing;

use Illuminate\Support\Str;
use Platform\Billing\Enums\BillingPaymentIntentStatus;
use Platform\Billing\Enums\InvoiceStatus;
use Platform\Billing\Enums\SubscriptionStatus;
use Platform\Billing\Models\BillingPaymentIntent;
use Platform\Billing\Models\Invoice;
use Platform\Billing\Models\Plan;
use Platform\Billing\Models\Subscription;
use Platform\Identity\Enums\MerchantUserRole;
use Platform\Tenancy\Models\Tenant;
use Tests\Feature\PlatformTestCase;

final class BillingSubscriptionPaymentTest extends PlatformTestCase
{
    public function test_owner_can_initialize_platform_subscription_payment(): void
    {
        $tenant = $this->createTenant();
        $merchant = $this->createMerchantForTenant($tenant, 'billing-pay@test.com', 'password12345');
        $plan = Plan::query()->where('slug', 'starter')->firstOrFail();

        Subscription::query()->create([
            'tenant_id' => $tenant->id,
            'plan_id' => $plan->id,
            'status' => SubscriptionStatus::Trial,
            'trial_ends_at' => now()->addDays(3),
        ]);

        $token = $this->loginMerchant($merchant->email, 'password12345');

        $response = $this->postJson(
            "/api/v1/platform/billing/subscriptions/{$tenant->id}/initialize-payment",
            ['email' => $merchant->email],
            array_merge(
                $this->merchantAuthHeaders($tenant->id, $token),
                $this->idempotencyHeaders(),
            ),
        );

        $response->assertOk()
            ->assertJsonStructure(['data' => ['authorization_url', 'reference']]);

        $reference = (string) $response->json('data.reference');

        $this->assertDatabaseHas('billing_payment_intents', [
            'tenant_id' => $tenant->id,
            'paystack_reference' => $reference,
            'status' => BillingPaymentIntentStatus::Pending->value,
            'amount_kobo' => $plan->price_ngn,
        ]);
    }

    public function test_platform_subscription_webhook_activates_subscription(): void
    {
        $tenant = $this->createTenant();
        $plan = Plan::query()->where('slug', 'starter')->firstOrFail();

        $subscription = Subscription::query()->create([
            'tenant_id' => $tenant->id,
            'plan_id' => $plan->id,
            'status' => SubscriptionStatus::Trial,
            'trial_ends_at' => now()->addDay(),
        ]);

        $reference = 'saas_'.$tenant->id.'_'.Str::lower(Str::random(8));

        BillingPaymentIntent::query()->create([
            'tenant_id' => $tenant->id,
            'subscription_id' => $subscription->id,
            'paystack_reference' => $reference,
            'amount_kobo' => $plan->price_ngn,
            'status' => BillingPaymentIntentStatus::Pending,
        ]);

        $this->postJson('/api/v1/webhooks/paystack', [
            'event' => 'charge.success',
            'data' => [
                'reference' => $reference,
                'amount' => $plan->price_ngn,
                'status' => 'success',
                'metadata' => [
                    'billing_type' => 'platform_subscription',
                    'tenant_id' => $tenant->id,
                ],
            ],
        ])->assertOk();

        $this->assertDatabaseHas('subscriptions', [
            'tenant_id' => $tenant->id,
            'status' => SubscriptionStatus::Active->value,
        ]);

        $this->assertDatabaseHas('invoices', [
            'tenant_id' => $tenant->id,
            'status' => InvoiceStatus::Paid->value,
        ]);

        $this->assertDatabaseHas('billing_payment_intents', [
            'paystack_reference' => $reference,
            'status' => BillingPaymentIntentStatus::Completed->value,
        ]);
    }

    public function test_finance_can_view_subscription_and_invoices(): void
    {
        $tenant = $this->createTenant();
        $finance = $this->createMerchantForTenant(
            $tenant,
            'finance-view@test.com',
            'password12345',
            MerchantUserRole::Finance,
        );
        $plan = Plan::query()->where('slug', 'starter')->firstOrFail();

        Subscription::query()->create([
            'tenant_id' => $tenant->id,
            'plan_id' => $plan->id,
            'status' => SubscriptionStatus::Active,
            'current_period_end' => now()->addMonth(),
        ]);

        Invoice::query()->create([
            'tenant_id' => $tenant->id,
            'number' => 'INV-TEST-001',
            'status' => InvoiceStatus::Paid,
            'currency' => 'NGN',
            'subtotal' => $plan->price_ngn,
            'tax' => 0,
            'total' => $plan->price_ngn,
            'period_start' => now()->startOfMonth(),
            'period_end' => now()->endOfMonth(),
            'lines' => [['description' => 'Starter subscription', 'amount' => $plan->price_ngn]],
        ]);

        $token = $this->loginMerchant($finance->email, 'password12345');
        $headers = $this->merchantAuthHeaders($tenant->id, $token);

        $this->getJson('/api/v1/platform/billing/subscription', $headers)
            ->assertOk()
            ->assertJsonPath('data.status', 'active')
            ->assertJsonPath('data.plan.slug', 'starter');

        $this->getJson('/api/v1/platform/billing/invoices', $headers)
            ->assertOk()
            ->assertJsonCount(1, 'data');
    }

    private function createTenant(): Tenant
    {
        return Tenant::query()->create([
            'slug' => 'billing-pay-'.Str::random(6),
            'name' => 'Billing Pay Tenant',
            'status' => 'active',
            'country' => 'NG',
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
