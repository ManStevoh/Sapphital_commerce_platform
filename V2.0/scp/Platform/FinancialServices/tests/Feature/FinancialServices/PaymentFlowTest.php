<?php

declare(strict_types=1);

namespace Platform\FinancialServices\Tests\Feature\FinancialServices;

use Illuminate\Support\Str;
use Modules\Commerce\Cart\Models\Cart;
use Modules\Commerce\Cart\Models\CartItem;
use Modules\Commerce\Catalog\Models\Product;
use Modules\Commerce\Checkout\Models\CheckoutSession;
use Platform\Tenancy\Models\Tenant;
use Tests\Feature\PlatformTestCase;

final class PaymentFlowTest extends PlatformTestCase
{
    public function test_full_checkout_payment_flow(): void
    {
        $tenant = $this->createTenant();
        $cart = $this->createCartWithItems($tenant, 3_000_000);

        $checkout = $this->postJson('/api/v1/commerce/checkout/sessions', [
            'cart_id' => $cart->id,
        ], $this->tenantMoneyHeaders($tenant->id));

        $checkout->assertCreated()
            ->assertJsonPath('data.status', 'pending')
            ->assertJsonPath('data.total_kobo', 3_000_000);

        $sessionId = $checkout->json('data.session_id');

        $initialize = $this->postJson('/api/v1/platform/financial-services/payments/initialize', [
            'checkout_session_id' => $sessionId,
            'email' => 'buyer@example.com',
        ], $this->tenantMoneyHeaders($tenant->id));

        $initialize->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'authorization_url',
                    'reference',
                ],
            ]);

        $reference = $initialize->json('data.reference');
        $authorizationUrl = $initialize->json('data.authorization_url');

        $this->assertNotEmpty($reference);
        $this->assertNotEmpty($authorizationUrl);

        $this->assertDatabaseHas('checkout_sessions', [
            'id' => $sessionId,
            'tenant_id' => $tenant->id,
            'paystack_reference' => $reference,
            'status' => CheckoutSession::STATUS_PENDING,
        ]);

        $verify = $this->postJson('/api/v1/platform/financial-services/payments/verify', [
            'reference' => $reference,
        ], $this->tenantMoneyHeaders($tenant->id));

        $verify->assertOk()
            ->assertJsonPath('data.status', CheckoutSession::STATUS_COMPLETED)
            ->assertJsonPath('data.reference', $reference)
            ->assertJsonPath('data.checkout_session_id', $sessionId);

        $this->assertDatabaseHas('checkout_sessions', [
            'id' => $sessionId,
            'tenant_id' => $tenant->id,
            'paystack_reference' => $reference,
            'status' => CheckoutSession::STATUS_COMPLETED,
        ]);
    }

    public function test_initialize_requires_tenant_context(): void
    {
        $response = $this->postJson('/api/v1/platform/financial-services/payments/initialize', [
            'checkout_session_id' => (string) Str::uuid(),
            'email' => 'buyer@example.com',
        ], $this->idempotencyHeaders());

        $response->assertForbidden()
            ->assertJson([
                'message' => 'Tenant context required.',
            ]);
    }

    public function test_verify_rejects_reference_from_other_tenant(): void
    {
        $tenant = $this->createTenant();
        $otherTenant = $this->createTenant('other');
        $cart = $this->createCartWithItems($tenant, 1_500_000);

        $checkout = $this->postJson('/api/v1/commerce/checkout/sessions', [
            'cart_id' => $cart->id,
        ], $this->tenantMoneyHeaders($tenant->id));

        $sessionId = $checkout->json('data.session_id');

        $initialize = $this->postJson('/api/v1/platform/financial-services/payments/initialize', [
            'checkout_session_id' => $sessionId,
            'email' => 'buyer@example.com',
        ], $this->tenantMoneyHeaders($tenant->id));

        $reference = $initialize->json('data.reference');

        $verify = $this->postJson('/api/v1/platform/financial-services/payments/verify', [
            'reference' => $reference,
        ], $this->tenantMoneyHeaders($otherTenant->id));

        $verify->assertNotFound()
            ->assertJson([
                'message' => 'Checkout session not found for reference.',
            ]);
    }

    private function createTenant(string $prefix = 'payment'): Tenant
    {
        return Tenant::query()->create([
            'slug' => $prefix.'-'.Str::random(8),
            'name' => ucfirst($prefix).' Tenant',
            'status' => 'active',
            'country' => 'NG',
        ]);
    }

    private function createCartWithItems(Tenant $tenant, int $lineTotalKobo): Cart
    {
        $product = Product::query()->create([
            'tenant_id' => $tenant->id,
            'name' => 'Payment Product',
            'slug' => 'payment-product-'.Str::random(6),
            'price_kobo' => $lineTotalKobo,
            'status' => 'published',
            'inventory_qty' => 5,
        ]);

        $cart = Cart::query()->create([
            'tenant_id' => $tenant->id,
            'session_id' => (string) Str::uuid(),
            'currency' => 'NGN',
        ]);

        CartItem::query()->create([
            'cart_id' => $cart->id,
            'product_id' => $product->id,
            'quantity' => 1,
            'unit_price_kobo' => $lineTotalKobo,
            'line_total_kobo' => $lineTotalKobo,
        ]);

        return $cart;
    }
}
