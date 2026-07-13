<?php

declare(strict_types=1);

namespace Tests\Feature;

use Connectors\Paystack\PaystackConnectorInterface;
use Modules\Commerce\Catalog\Models\Product;

final class SystemFlowTest extends PlatformTestCase
{
    public function test_merchant_onboarding_to_catalog_flow(): void
    {
        $signup = $this->postJson('/api/v1/signup', [
            'email' => 'flow@example.com',
            'password' => 'secure-password-123',
            'store_name' => 'Flow Fashion',
            'plan_slug' => 'starter',
        ]);

        $signup->assertAccepted();

        $tenantId = $signup->json('tenant_id');
        $pollUrl = $signup->json('poll_url');

        $this->assertNotEmpty($tenantId);
        $this->assertStringContainsString($tenantId, (string) $pollUrl);

        $status = $this->getJson($pollUrl);
        $status->assertOk()
            ->assertJsonPath('status', 'completed');

        $this->assertSame(3, Product::query()->where('tenant_id', $tenantId)->count());

        $login = $this->postJson('/api/v1/auth/merchant/login', [
            'email' => 'flow@example.com',
            'password' => 'secure-password-123',
        ]);

        $login->assertOk();
        $token = $login->json('token');
        $this->assertNotEmpty($token);

        $me = $this->getJson('/api/v1/auth/me', [
            'Authorization' => 'Bearer '.$token,
        ]);

        $me->assertOk()
            ->assertJsonPath('email', 'flow@example.com');

        $subscription = $this->getJson("/api/v1/platform/billing/subscriptions/{$tenantId}");
        $subscription->assertOk()
            ->assertJsonPath('data.status', 'trial')
            ->assertJsonPath('data.plan.slug', 'starter');

        $products = $this->getJson('/api/v1/commerce/catalog/products', [
            'X-Tenant-ID' => $tenantId,
        ]);

        $products->assertOk()
            ->assertJsonCount(3, 'data');

        $create = $this->postJson('/api/v1/commerce/catalog/products', [
            'name' => 'Flow Exclusive',
            'price_kobo' => 9_900_000,
            'status' => 'published',
            'inventory_qty' => 2,
        ], $this->merchantAuthHeaders($tenantId, $token));

        $create->assertCreated()
            ->assertJsonPath('data.name', 'Flow Exclusive');

        $this->getJson('/api/v1/commerce/catalog/products', [
            'X-Tenant-ID' => $tenantId,
        ])->assertJsonCount(4, 'data');

        $sessionId = (string) \Illuminate\Support\Str::uuid();
        $productId = $create->json('data.id');

        $this->postJson('/api/v1/commerce/cart/items', [
            'product_id' => $productId,
            'quantity' => 2,
        ], [
            'X-Tenant-ID' => $tenantId,
            'X-Session-ID' => $sessionId,
        ])->assertCreated()
            ->assertJsonPath('data.item.quantity', 2);

        $cart = $this->getJson('/api/v1/commerce/cart', [
            'X-Tenant-ID' => $tenantId,
            'X-Session-ID' => $sessionId,
        ]);

        $cart->assertOk()
            ->assertJsonPath('data.total_kobo', 19_800_000);

        $cartId = $cart->json('data.id');

        $checkout = $this->postJson('/api/v1/commerce/checkout/sessions', [
            'cart_id' => $cartId,
        ], array_merge(
            $this->tenantMoneyHeaders($tenantId),
            ['X-Session-ID' => $sessionId],
        ));

        $checkoutSessionId = $checkout->json('data.session_id');

        $initialize = $this->postJson('/api/v1/platform/financial-services/payments/initialize', [
            'checkout_session_id' => $checkoutSessionId,
            'email' => 'buyer@flow-fashion.test',
        ], $this->tenantMoneyHeaders($tenantId));

        $initialize->assertOk()
            ->assertJsonStructure(['data' => ['authorization_url', 'reference']]);

        $reference = $initialize->json('data.reference');

        $verify = $this->postJson('/api/v1/platform/financial-services/payments/verify', [
            'reference' => $reference,
        ], $this->tenantMoneyHeaders($tenantId));

        $verify->assertOk()
            ->assertJsonPath('data.status', 'completed')
            ->assertJsonStructure(['data' => ['order_id']]);

        $orderId = $verify->json('data.order_id');
        $this->assertNotEmpty($orderId);

        $this->getJson("/api/v1/commerce/orders/{$orderId}", [
            'X-Tenant-ID' => $tenantId,
        ])->assertOk()
            ->assertJsonPath('data.status', 'paid')
            ->assertJsonPath('data.total_kobo', 19_800_000)
            ->assertJsonCount(1, 'data.items');
    }

    public function test_paystack_connector_is_registered(): void
    {
        $connector = $this->app->make(PaystackConnectorInterface::class);

        $init = $connector->initializeTransaction([
            'email' => 'buyer@example.com',
            'amount' => 150_000,
        ]);

        $this->assertTrue($init['status']);
        $reference = $init['data']['reference'];
        $this->assertNotEmpty($reference);

        $verify = $connector->verifyTransaction($reference);
        $this->assertSame('success', $verify['data']['status']);
    }
}
