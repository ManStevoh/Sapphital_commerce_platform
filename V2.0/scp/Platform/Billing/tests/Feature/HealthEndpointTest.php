<?php

declare(strict_types=1);

namespace Platform\Billing\Tests\Feature;

use Platform\Billing\Tests\TestCase;

final class HealthEndpointTest extends TestCase
{
    public function test_health_endpoint_returns_ok(): void
    {
        $response = $this->getJson('/api/v1/platform/billing/health');

        $response->assertOk()
            ->assertJson([
                'status' => 'ok',
                'package' => 'billing',
            ]);
    }
}
