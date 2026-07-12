<?php

declare(strict_types=1);

namespace Tests\Feature;

use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

final class PlatformHealthEndpointTest extends TestCase
{
    /**
     * @return array<string, array{0: string, 1: string}>
     */
    public static function providePlatformHealthEndpoints(): array
    {
        return [
            'tenancy' => ['tenancy', 'tenancy'],
            'identity' => ['identity', 'identity'],
            'billing' => ['billing', 'billing'],
            'provisioning' => ['provisioning', 'provisioning'],
            'financial-services' => ['financial-services', 'financial-services'],
            'secrets' => ['secrets', 'secrets'],
            'notifications' => ['notifications', 'notifications'],
        ];
    }

    #[DataProvider('providePlatformHealthEndpoints')]
    public function test_platform_health_endpoint_returns_ok(string $path, string $package): void
    {
        $response = $this->getJson("/api/v1/platform/{$path}/health");

        $response->assertOk()
            ->assertJson([
                'status' => 'ok',
                'package' => $package,
            ]);
    }
}
