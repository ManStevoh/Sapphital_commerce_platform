<?php

declare(strict_types=1);

namespace Tests\Feature\Orders;

use Tests\Feature\PlatformTestCase;

final class OrdersHealthEndpointTest extends PlatformTestCase
{
    public function test_orders_health_endpoint_returns_ok(): void
    {
        $response = $this->getJson('/api/v1/commerce/orders/health');

        $response->assertOk()
            ->assertJson([
                'status' => 'ok',
                'package' => 'orders',
            ]);
    }
}
