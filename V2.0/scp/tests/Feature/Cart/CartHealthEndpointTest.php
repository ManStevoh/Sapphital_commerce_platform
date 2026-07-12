<?php

declare(strict_types=1);

namespace Tests\Feature\Cart;

use Tests\Feature\PlatformTestCase;

final class CartHealthEndpointTest extends PlatformTestCase
{
    public function test_cart_health_endpoint_returns_ok(): void
    {
        $response = $this->getJson('/api/v1/commerce/cart/health');

        $response->assertOk()
            ->assertJson([
                'status' => 'ok',
                'package' => 'cart',
            ]);
    }
}
