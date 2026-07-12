<?php

declare(strict_types=1);

namespace Platform\FinancialServices\Tests\Feature;

use Orchestra\Testbench\TestCase;
use Platform\FinancialServices\FinancialServicesServiceProvider;

final class HealthEndpointTest extends TestCase
{
    /**
     * @param  \Illuminate\Foundation\Application  $app
     * @return array<int, class-string>
     */
    protected function getPackageProviders($app): array
    {
        return [FinancialServicesServiceProvider::class];
    }

    public function test_health_endpoint_returns_ok(): void
    {
        $response = $this->getJson('/api/v1/platform/financial-services/health');

        $response->assertOk()
            ->assertJson([
                'status' => 'ok',
                'package' => 'financial-services',
            ]);
    }
}
