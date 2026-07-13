<?php

declare(strict_types=1);

namespace Tests\Feature\Checkout;

use Illuminate\Support\Str;
use Modules\Commerce\Checkout\Models\CheckoutSession;
use Platform\Tenancy\Models\Tenant;
use Tests\Feature\PlatformTestCase;

final class IdempotencyTest extends PlatformTestCase
{
    public function test_checkout_create_requires_idempotency_key(): void
    {
        $tenant = $this->createTenant();
        $cart = $this->createCartWithItems($tenant, 1_000_000);

        $response = $this->postJson('/api/v1/commerce/checkout/sessions', [
            'cart_id' => $cart->id,
        ], [
            'X-Tenant-ID' => $tenant->id,
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['idempotency_key']);
    }

    public function test_checkout_create_replays_identical_response(): void
    {
        $tenant = $this->createTenant();
        $cart = $this->createCartWithItems($tenant, 2_000_000);
        $key = (string) Str::uuid();
        $headers = $this->tenantMoneyHeaders($tenant->id, $key);
        $payload = ['cart_id' => $cart->id];

        $first = $this->postJson('/api/v1/commerce/checkout/sessions', $payload, $headers);
        $second = $this->postJson('/api/v1/commerce/checkout/sessions', $payload, $headers);

        $first->assertCreated();
        $second->assertCreated()
            ->assertExactJson($first->json());

        $this->assertSame(
            1,
            CheckoutSession::query()->where('tenant_id', $tenant->id)->count(),
        );
    }

    public function test_checkout_create_rejects_key_reuse_with_different_body(): void
    {
        $tenant = $this->createTenant();
        $cartA = $this->createCartWithItems($tenant, 1_000_000);
        $cartB = $this->createCartWithItems($tenant, 2_000_000);
        $key = (string) Str::uuid();
        $headers = $this->tenantMoneyHeaders($tenant->id, $key);

        $this->postJson('/api/v1/commerce/checkout/sessions', [
            'cart_id' => $cartA->id,
        ], $headers)->assertCreated();

        $this->postJson('/api/v1/commerce/checkout/sessions', [
            'cart_id' => $cartB->id,
        ], $headers)
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['idempotency_key']);
    }

    public function test_payment_initialize_replays_same_reference(): void
    {
        $tenant = $this->createTenant();
        $cart = $this->createCartWithItems($tenant, 3_000_000);

        $checkout = $this->postJson('/api/v1/commerce/checkout/sessions', [
            'cart_id' => $cart->id,
        ], $this->tenantMoneyHeaders($tenant->id));

        $sessionId = $checkout->json('data.session_id');
        $key = (string) Str::uuid();
        $headers = $this->tenantMoneyHeaders($tenant->id, $key);
        $payload = [
            'checkout_session_id' => $sessionId,
            'email' => 'buyer@example.com',
        ];

        $first = $this->postJson('/api/v1/platform/financial-services/payments/initialize', $payload, $headers);
        $second = $this->postJson('/api/v1/platform/financial-services/payments/initialize', $payload, $headers);

        $first->assertOk();
        $second->assertOk()
            ->assertExactJson($first->json());

        $this->assertSame(
            1,
            CheckoutSession::query()
                ->where('id', $sessionId)
                ->whereNotNull('paystack_reference')
                ->count(),
        );
    }

    private function createTenant(string $prefix = 'idempotency'): Tenant
    {
        return Tenant::query()->create([
            'slug' => $prefix.'-'.Str::random(8),
            'name' => ucfirst($prefix).' Tenant',
            'status' => 'active',
            'country' => 'NG',
        ]);
    }

    private function createCartWithItems(Tenant $tenant, int $lineTotalKobo): \Modules\Commerce\Cart\Models\Cart
    {
        $product = \Modules\Commerce\Catalog\Models\Product::query()->create([
            'tenant_id' => $tenant->id,
            'name' => 'Idempotency Product',
            'slug' => 'idempotency-product-'.Str::random(6),
            'price_kobo' => $lineTotalKobo,
            'status' => 'published',
            'inventory_qty' => 5,
        ]);

        $cart = \Modules\Commerce\Cart\Models\Cart::query()->create([
            'tenant_id' => $tenant->id,
            'session_id' => (string) Str::uuid(),
            'currency' => 'NGN',
        ]);

        \Modules\Commerce\Cart\Models\CartItem::query()->create([
            'cart_id' => $cart->id,
            'product_id' => $product->id,
            'quantity' => 1,
            'unit_price_kobo' => $lineTotalKobo,
            'line_total_kobo' => $lineTotalKobo,
        ]);

        return $cart;
    }
}
