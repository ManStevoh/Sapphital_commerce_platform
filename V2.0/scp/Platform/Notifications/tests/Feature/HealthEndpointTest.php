<?php

declare(strict_types=1);

namespace Platform\Notifications\Tests\Feature;

use Orchestra\Testbench\TestCase;
use Platform\Notifications\NotificationsServiceProvider;

final class HealthEndpointTest extends TestCase
{
    /**
     * @param  \Illuminate\Foundation\Application  $app
     * @return array<int, class-string>
     */
    protected function getPackageProviders($app): array
    {
        return [NotificationsServiceProvider::class];
    }

    public function test_health_endpoint_returns_ok(): void
    {
        $response = $this->getJson('/api/v1/platform/notifications/health');

        $response->assertOk()
            ->assertJson([
                'status' => 'ok',
                'package' => 'notifications',
            ]);
    }
}
