<?php

declare(strict_types=1);

namespace Tests\Feature\Infrastructure;

use Tests\TestCase;

/**
 * Programmatic mirror of scripts/health-check.sh — keep endpoint lists in sync.
 *
 * @see scripts/health-check.sh
 */
final class HealthCheckScriptTest extends TestCase
{
    public function test_all_health_check_endpoints_return_ok(): void
    {
        $endpoints = [
            ['/api/health', ['status' => 'ok', 'service' => 'scp-api']],
            ['/api/v1/platform/tenancy/health', ['status' => 'ok', 'package' => 'tenancy']],
            ['/api/v1/platform/identity/health', ['status' => 'ok', 'package' => 'identity']],
            ['/api/v1/platform/billing/health', ['status' => 'ok', 'package' => 'billing']],
            ['/api/v1/platform/provisioning/health', ['status' => 'ok', 'package' => 'provisioning']],
            ['/api/v1/platform/financial-services/health', ['status' => 'ok', 'package' => 'financial-services']],
            ['/api/v1/platform/secrets/health', ['status' => 'ok', 'package' => 'secrets']],
            ['/api/v1/platform/notifications/health', ['status' => 'ok', 'package' => 'notifications']],
            ['/api/v1/commerce/catalog/health', ['status' => 'ok', 'package' => 'catalog']],
            ['/api/v1/commerce/cart/health', ['status' => 'ok', 'package' => 'cart']],
            ['/api/v1/commerce/checkout/health', ['status' => 'ok', 'package' => 'checkout']],
            ['/api/v1/commerce/orders/health', ['status' => 'ok', 'package' => 'orders']],
            ['/api/v1/commerce/shipping/health', ['status' => 'ok', 'package' => 'shipping']],
        ];

        foreach ($endpoints as [$path, $expected]) {
            $response = $this->getJson($path);

            $response->assertOk()->assertJson($expected);
        }
    }
}
