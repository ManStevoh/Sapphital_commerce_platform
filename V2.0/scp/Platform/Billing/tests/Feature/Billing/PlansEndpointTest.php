<?php

declare(strict_types=1);

namespace Platform\Billing\Tests\Feature\Billing;

use Platform\Billing\Tests\TestCase;

final class PlansEndpointTest extends TestCase
{
    public function test_plans_endpoint_returns_three_default_plans(): void
    {
        $response = $this->getJson('/api/v1/platform/billing/plans');

        $response->assertOk()
            ->assertJsonCount(3, 'data')
            ->assertJsonPath('data.0.slug', 'starter')
            ->assertJsonPath('data.0.price_ngn', 1_500_000)
            ->assertJsonPath('data.1.slug', 'growth')
            ->assertJsonPath('data.1.price_ngn', 4_500_000)
            ->assertJsonPath('data.2.slug', 'pro')
            ->assertJsonPath('data.2.price_ngn', 12_000_000);
    }
}
