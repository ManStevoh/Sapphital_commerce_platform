<?php

declare(strict_types=1);

namespace Tests\Feature\Checkout;

use Tests\Feature\PlatformTestCase;

final class CheckoutHealthEndpointTest extends PlatformTestCase
{
    public function test_checkout_health_endpoint_returns_ok(): void
    {
        $response = $this->getJson('/api/v1/commerce/checkout/health');

        $response->assertOk()
            ->assertJson([
                'status' => 'ok',
                'package' => 'checkout',
            ]);
    }
}
