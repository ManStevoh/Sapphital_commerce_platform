<?php

namespace Tests\Feature;

use Tests\TestCase;

class HealthEndpointTest extends TestCase
{
    public function test_health_endpoint_returns_ok(): void
    {
        $response = $this->getJson('/api/health');

        $response->assertOk()
            ->assertJson([
                'status' => 'ok',
                'service' => 'scp-api',
            ]);
    }

    public function test_ready_endpoint_returns_ready_when_database_is_available(): void
    {
        $response = $this->getJson('/api/ready');

        $response->assertOk()
            ->assertJson([
                'status' => 'ready',
                'service' => 'scp-api',
            ]);
    }
}
