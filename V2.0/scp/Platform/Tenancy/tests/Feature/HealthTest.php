<?php

declare(strict_types=1);

namespace Platform\Tenancy\Tests\Feature;

use Orchestra\Testbench\TestCase;
use Platform\Tenancy\TenancyServiceProvider;

final class HealthTest extends TestCase
{
    /**
     * @param  \Illuminate\Foundation\Application  $app
     * @return array<int, class-string>
     */
    protected function getPackageProviders($app): array
    {
        return [TenancyServiceProvider::class];
    }

    public function test_health_endpoint_returns_ok(): void
    {
        $response = $this->getJson('/api/v1/platform/tenancy/health');

        $response->assertOk()
            ->assertJson([
                'status' => 'ok',
                'package' => 'tenancy',
            ]);
    }
}
