<?php

declare(strict_types=1);

namespace Tests\Feature\Billing;

use Illuminate\Support\Str;
use Platform\Billing\Enums\SubscriptionStatus;
use Platform\Billing\Models\Plan;
use Platform\Billing\Models\Subscription;
use Tests\Feature\PlatformTestCase;

final class SubscriptionEndpointTest extends PlatformTestCase
{
    public function test_subscription_endpoint_returns_tenant_subscription(): void
    {
        $tenantId = (string) Str::uuid();
        $plan = Plan::query()->where('slug', 'starter')->firstOrFail();

        Subscription::query()->create([
            'tenant_id' => $tenantId,
            'plan_id' => $plan->id,
            'status' => SubscriptionStatus::Trial,
        ]);

        $response = $this->getJson("/api/v1/platform/billing/subscriptions/{$tenantId}");

        $response->assertOk()
            ->assertJsonPath('data.tenant_id', $tenantId)
            ->assertJsonPath('data.status', 'trial')
            ->assertJsonPath('data.plan.slug', 'starter');
    }

    public function test_subscription_endpoint_returns_404_when_missing(): void
    {
        $response = $this->getJson('/api/v1/platform/billing/subscriptions/'.Str::uuid());

        $response->assertNotFound()
            ->assertJson([
                'message' => 'Subscription not found.',
            ]);
    }
}
