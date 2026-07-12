<?php

declare(strict_types=1);

namespace Tests\Feature\Checkout;

use Illuminate\Support\Str;
use Modules\Commerce\Cart\Models\Cart;
use Modules\Commerce\Cart\Models\CartItem;
use Modules\Commerce\Catalog\Models\Product;
use Platform\Tenancy\Models\Tenant;
use Tests\Feature\PlatformTestCase;

final class CheckoutSessionEndpointTest extends PlatformTestCase
{
    public function test_create_session_from_cart_with_items(): void
    {
        $tenant = $this->createTenant();
        $cart = $this->createCartWithItems($tenant, 3_000_000);

        $response = $this->postJson('/api/v1/commerce/checkout/sessions', [
            'cart_id' => $cart->id,
        ], [
            'X-Tenant-ID' => $tenant->id,
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.total_kobo', 3_000_000)
            ->assertJsonPath('data.status', 'pending');

        $sessionId = $response->json('data.session_id');

        $this->assertNotNull($sessionId);
        $this->assertDatabaseHas('checkout_sessions', [
            'id' => $sessionId,
            'tenant_id' => $tenant->id,
            'cart_id' => $cart->id,
            'status' => 'pending',
            'total_kobo' => 3_000_000,
        ]);
    }

    public function test_create_session_rejects_empty_cart(): void
    {
        $tenant = $this->createTenant();

        $cart = Cart::query()->create([
            'tenant_id' => $tenant->id,
            'session_id' => (string) Str::uuid(),
            'currency' => 'NGN',
        ]);

        $response = $this->postJson('/api/v1/commerce/checkout/sessions', [
            'cart_id' => $cart->id,
        ], [
            'X-Tenant-ID' => $tenant->id,
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['cart_id']);
    }

    public function test_create_session_requires_tenant_context(): void
    {
        $response = $this->postJson('/api/v1/commerce/checkout/sessions', [
            'cart_id' => (string) Str::uuid(),
        ]);

        $response->assertForbidden()
            ->assertJson([
                'message' => 'Tenant context required.',
            ]);
    }

    public function test_create_session_rejects_cart_from_other_tenant(): void
    {
        $tenant = $this->createTenant();
        $otherTenant = $this->createTenant('other');
        $cart = $this->createCartWithItems($otherTenant, 1_000);

        $response = $this->postJson('/api/v1/commerce/checkout/sessions', [
            'cart_id' => $cart->id,
        ], [
            'X-Tenant-ID' => $tenant->id,
        ]);

        $response->assertNotFound()
            ->assertJson([
                'message' => 'Cart not found.',
            ]);
    }

    private function createTenant(string $prefix = 'checkout'): Tenant
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
            'name' => 'Checkout Product',
            'slug' => 'checkout-product-'.Str::random(6),
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
