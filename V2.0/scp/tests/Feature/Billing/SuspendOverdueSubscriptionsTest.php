<?php

declare(strict_types=1);

namespace Tests\Feature\Billing;

use Illuminate\Support\Str;
use Platform\Billing\Enums\SubscriptionStatus;
use Platform\Billing\Models\Plan;
use Platform\Billing\Models\Subscription;
use Platform\Tenancy\Models\Tenant;
use Tests\Feature\PlatformTestCase;

final class SuspendOverdueSubscriptionsTest extends PlatformTestCase
{
    public function test_overdue_past_due_subscription_suspends_tenant_storefront(): void
    {
        $tenant = $this->createTenant('trial');
        $plan = Plan::query()->where('slug', 'starter')->firstOrFail();

        Subscription::query()->create([
            'tenant_id' => $tenant->id,
            'plan_id' => $plan->id,
            'status' => SubscriptionStatus::PastDue,
            'past_due_at' => now()->subDays(15),
        ]);

        $this->artisan('scp:suspend-overdue-subscriptions')
            ->assertSuccessful();

        $this->assertDatabaseHas('subscriptions', [
            'tenant_id' => $tenant->id,
            'status' => SubscriptionStatus::Suspended->value,
        ]);

        $tenant->refresh();
        $this->assertSame('suspended', $tenant->status);
    }

    public function test_recent_past_due_subscription_is_not_suspended(): void
    {
        $tenant = $this->createTenant('trial');
        $plan = Plan::query()->where('slug', 'starter')->firstOrFail();

        Subscription::query()->create([
            'tenant_id' => $tenant->id,
            'plan_id' => $plan->id,
            'status' => SubscriptionStatus::PastDue,
            'past_due_at' => now()->subDays(3),
        ]);

        $this->artisan('scp:suspend-overdue-subscriptions')
            ->assertSuccessful();

        $this->assertDatabaseHas('subscriptions', [
            'tenant_id' => $tenant->id,
            'status' => SubscriptionStatus::PastDue->value,
        ]);

        $tenant->refresh();
        $this->assertSame('trial', $tenant->status);
    }

    public function test_payment_activation_restores_suspended_tenant(): void
    {
        $tenant = $this->createTenant('suspended');
        $merchant = $this->createMerchantForTenant($tenant, 'restore@test.com', 'password12345');
        $plan = Plan::query()->where('slug', 'starter')->firstOrFail();

        Subscription::query()->create([
            'tenant_id' => $tenant->id,
            'plan_id' => $plan->id,
            'status' => SubscriptionStatus::Suspended,
            'past_due_at' => now()->subDays(20),
        ]);

        $token = $this->loginMerchant($merchant->email, 'password12345');

        $this->postJson(
            "/api/v1/platform/billing/subscriptions/{$tenant->id}/activate",
            ['paystack_reference' => 'saas_restore_'.Str::random(6)],
            $this->merchantAuthHeaders($tenant->id, $token),
        )->assertOk()
            ->assertJsonPath('data.subscription.status', 'active');

        $tenant->refresh();
        $this->assertSame('active', $tenant->status);

        $this->assertDatabaseHas('subscriptions', [
            'tenant_id' => $tenant->id,
            'status' => SubscriptionStatus::Active->value,
            'past_due_at' => null,
        ]);
    }

    public function test_expired_trials_command_sets_past_due_at(): void
    {
        $tenant = $this->createTenant('trial');
        $plan = Plan::query()->where('slug', 'starter')->firstOrFail();

        Subscription::query()->create([
            'tenant_id' => $tenant->id,
            'plan_id' => $plan->id,
            'status' => SubscriptionStatus::Trial,
            'trial_ends_at' => now()->subDay(),
        ]);

        $this->artisan('scp:process-expired-trials')
            ->assertSuccessful();

        $subscription = Subscription::query()->where('tenant_id', $tenant->id)->firstOrFail();
        $this->assertSame(SubscriptionStatus::PastDue, $subscription->status);
        $this->assertNotNull($subscription->past_due_at);
    }

    private function createTenant(string $status): Tenant
    {
        return Tenant::query()->create([
            'slug' => 'dunning-'.Str::random(6),
            'name' => 'Dunning Tenant',
            'status' => $status,
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
