<?php

declare(strict_types=1);

namespace Tests\Feature\Billing;

use Illuminate\Support\Str;
use Platform\Billing\Enums\InvoiceStatus;
use Platform\Billing\Enums\SubscriptionStatus;
use Platform\Billing\Models\Invoice;
use Platform\Billing\Models\Plan;
use Platform\Billing\Models\Subscription;
use Platform\Tenancy\Models\Tenant;
use Tests\Feature\PlatformTestCase;

final class TrialToPaidCycleTest extends PlatformTestCase
{
    public function test_merchant_can_activate_trial_subscription(): void
    {
        $tenant = $this->createTenant();
        $merchant = $this->createMerchantForTenant($tenant, 'owner@billing.test', 'password12345');
        $plan = Plan::query()->where('slug', 'starter')->firstOrFail();

        Subscription::query()->create([
            'tenant_id' => $tenant->id,
            'plan_id' => $plan->id,
            'status' => SubscriptionStatus::Trial,
            'trial_ends_at' => now()->addDays(7),
        ]);

        $login = $this->postJson('/api/v1/auth/merchant/login', [
            'email' => $merchant->email,
            'password' => 'password12345',
        ]);

        $login->assertOk();
        $token = (string) $login->json('token');

        $response = $this->postJson(
            "/api/v1/platform/billing/subscriptions/{$tenant->id}/activate",
            ['paystack_reference' => 'saas_ref_'.Str::random(8)],
            $this->merchantAuthHeaders($tenant->id, $token),
        );

        $response->assertOk()
            ->assertJsonPath('data.subscription.status', 'active')
            ->assertJsonPath('data.invoice.status', 'paid')
            ->assertJsonPath('data.invoice.currency', 'NGN');

        $this->assertDatabaseHas('subscriptions', [
            'tenant_id' => $tenant->id,
            'status' => SubscriptionStatus::Active->value,
        ]);

        $this->assertDatabaseHas('invoices', [
            'tenant_id' => $tenant->id,
            'status' => InvoiceStatus::Paid->value,
            'total' => $plan->price_ngn,
        ]);
    }

    public function test_expired_trials_command_marks_subscriptions_past_due(): void
    {
        $tenant = $this->createTenant();
        $plan = Plan::query()->where('slug', 'starter')->firstOrFail();

        Subscription::query()->create([
            'tenant_id' => $tenant->id,
            'plan_id' => $plan->id,
            'status' => SubscriptionStatus::Trial,
            'trial_ends_at' => now()->subDay(),
        ]);

        $this->artisan('scp:process-expired-trials')
            ->assertSuccessful();

        $this->assertDatabaseHas('subscriptions', [
            'tenant_id' => $tenant->id,
            'status' => SubscriptionStatus::PastDue->value,
        ]);
    }

    public function test_past_due_subscription_can_be_reactivated(): void
    {
        $tenant = $this->createTenant();
        $merchant = $this->createMerchantForTenant($tenant, 'pastdue@billing.test', 'password12345');
        $plan = Plan::query()->where('slug', 'starter')->firstOrFail();

        Subscription::query()->create([
            'tenant_id' => $tenant->id,
            'plan_id' => $plan->id,
            'status' => SubscriptionStatus::PastDue,
            'trial_ends_at' => now()->subDays(3),
        ]);

        $login = $this->postJson('/api/v1/auth/merchant/login', [
            'email' => $merchant->email,
            'password' => 'password12345',
        ]);

        $token = (string) $login->json('token');

        $this->postJson(
            "/api/v1/platform/billing/subscriptions/{$tenant->id}/activate",
            [],
            $this->merchantAuthHeaders($tenant->id, $token),
        )->assertOk()
            ->assertJsonPath('data.subscription.status', 'active');

        $this->assertSame(
            1,
            Invoice::query()->where('tenant_id', $tenant->id)->count(),
        );
    }

    public function test_finance_role_cannot_activate_subscription(): void
    {
        $tenant = $this->createTenant();
        $merchant = $this->createMerchantForTenant(
            $tenant,
            'finance@billing.test',
            'password12345',
            \Platform\Identity\Enums\MerchantUserRole::Finance,
        );
        $plan = Plan::query()->where('slug', 'starter')->firstOrFail();

        Subscription::query()->create([
            'tenant_id' => $tenant->id,
            'plan_id' => $plan->id,
            'status' => SubscriptionStatus::Trial,
        ]);

        $login = $this->postJson('/api/v1/auth/merchant/login', [
            'email' => $merchant->email,
            'password' => 'password12345',
        ]);

        $token = (string) $login->json('token');

        $this->postJson(
            "/api/v1/platform/billing/subscriptions/{$tenant->id}/activate",
            [],
            $this->merchantAuthHeaders($tenant->id, $token),
        )->assertForbidden();
    }

    private function createTenant(): Tenant
    {
        return Tenant::query()->create([
            'slug' => 'billing-'.Str::random(6),
            'name' => 'Billing Tenant',
            'status' => 'active',
            'country' => 'NG',
        ]);
    }
}
