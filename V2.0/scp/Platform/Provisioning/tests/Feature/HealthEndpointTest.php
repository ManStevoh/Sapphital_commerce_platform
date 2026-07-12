<?php

declare(strict_types=1);

namespace Platform\Provisioning\Tests\Feature;

use Platform\Provisioning\Tests\TestCase;

final class HealthEndpointTest extends TestCase
{

    public function test_health_endpoint_returns_ok(): void
    {
        $response = $this->getJson('/api/v1/platform/provisioning/health');

        $response->assertOk()
            ->assertJson([
                'status' => 'ok',
                'package' => 'provisioning',
            ]);
    }
}
