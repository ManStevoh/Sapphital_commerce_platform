<?php

declare(strict_types=1);

namespace Tests\Feature\Shipping;

use Tests\Feature\PlatformTestCase;

final class ShippingHealthEndpointTest extends PlatformTestCase
{
    public function test_shipping_health_endpoint_returns_ok(): void
    {
        $response = $this->getJson('/api/v1/commerce/shipping/health');

        $response->assertOk()
            ->assertJson([
                'status' => 'ok',
                'package' => 'shipping',
            ]);
    }
}
