<?php

declare(strict_types=1);

namespace Tests\Feature\Cart;

use Illuminate\Support\Str;
use Modules\Commerce\Cart\Models\Cart;
use Modules\Commerce\Catalog\Models\Product;
use Platform\Tenancy\Models\Tenant;
use Tests\Feature\PlatformTestCase;

final class CartEndpointTest extends PlatformTestCase
{
    public function test_get_cart_creates_cart_for_session(): void
    {
        $tenant = $this->createTenant();
        $sessionId = (string) Str::uuid();

        $response = $this->getJson('/api/v1/commerce/cart', [
            'X-Tenant-ID' => $tenant->id,
            'X-Session-ID' => $sessionId,
        ]);

        $response->assertOk()
            ->assertJsonPath('data.tenant_id', $tenant->id)
            ->assertJsonPath('data.session_id', $sessionId)
            ->assertJsonPath('data.currency', 'NGN')
            ->assertJsonPath('data.total_kobo', 0)
            ->assertJsonCount(0, 'data.items');

        $this->assertDatabaseHas('carts', [
            'tenant_id' => $tenant->id,
            'session_id' => $sessionId,
            'currency' => 'NGN',
        ]);
    }

    public function test_get_cart_returns_existing_cart_for_session(): void
    {
        $tenant = $this->createTenant();
        $sessionId = (string) Str::uuid();

        $cart = Cart::query()->create([
            'tenant_id' => $tenant->id,
            'session_id' => $sessionId,
            'currency' => 'NGN',
        ]);

        $response = $this->getJson('/api/v1/commerce/cart', [
            'X-Tenant-ID' => $tenant->id,
            'X-Session-ID' => $sessionId,
        ]);

        $response->assertOk()
            ->assertJsonPath('data.id', $cart->id);
    }

    public function test_get_cart_requires_tenant_context(): void
    {
        $response = $this->getJson('/api/v1/commerce/cart', [
            'X-Session-ID' => (string) Str::uuid(),
        ]);

        $response->assertForbidden()
            ->assertJson([
                'message' => 'Tenant context required.',
            ]);
    }

    public function test_get_cart_requires_session_header(): void
    {
        $tenant = $this->createTenant();

        $response = $this->getJson('/api/v1/commerce/cart', [
            'X-Tenant-ID' => $tenant->id,
        ]);

        $response->assertUnprocessable()
            ->assertJson([
                'message' => 'X-Session-ID header required.',
            ]);
    }

    public function test_add_item_snapshots_price_and_totals(): void
    {
        $tenant = $this->createTenant();
        $sessionId = (string) Str::uuid();

        $product = Product::query()->create([
            'tenant_id' => $tenant->id,
            'name' => 'Ankara Dress',
            'slug' => 'ankara-dress',
            'price_kobo' => 2_500_000,
            'status' => 'published',
            'inventory_qty' => 10,
        ]);

        $response = $this->postJson('/api/v1/commerce/cart/items', [
            'product_id' => $product->id,
            'quantity' => 2,
        ], [
            'X-Tenant-ID' => $tenant->id,
            'X-Session-ID' => $sessionId,
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.item.product_id', $product->id)
            ->assertJsonPath('data.item.quantity', 2)
            ->assertJsonPath('data.item.unit_price_kobo', 2_500_000)
            ->assertJsonPath('data.item.line_total_kobo', 5_000_000)
            ->assertJsonPath('data.cart.total_kobo', 5_000_000);

        $this->assertDatabaseHas('cart_items', [
            'product_id' => $product->id,
            'quantity' => 2,
            'unit_price_kobo' => 2_500_000,
            'line_total_kobo' => 5_000_000,
        ]);
    }

    public function test_add_item_rejects_product_from_other_tenant(): void
    {
        $tenant = $this->createTenant();
        $otherTenant = $this->createTenant('other');

        $product = Product::query()->create([
            'tenant_id' => $otherTenant->id,
            'name' => 'Other Product',
            'slug' => 'other-product',
            'price_kobo' => 1_000,
            'status' => 'published',
            'inventory_qty' => 1,
        ]);

        $response = $this->postJson('/api/v1/commerce/cart/items', [
            'product_id' => $product->id,
            'quantity' => 1,
        ], [
            'X-Tenant-ID' => $tenant->id,
            'X-Session-ID' => (string) Str::uuid(),
        ]);

        $response->assertNotFound()
            ->assertJson([
                'message' => 'Product not found.',
            ]);
    }

    public function test_carts_are_isolated_by_tenant_and_session(): void
    {
        $tenantA = $this->createTenant('tenant-a');
        $tenantB = $this->createTenant('tenant-b');
        $sessionId = 'shared-session-id';

        Cart::query()->create([
            'tenant_id' => $tenantA->id,
            'session_id' => $sessionId,
            'currency' => 'NGN',
        ]);

        $response = $this->getJson('/api/v1/commerce/cart', [
            'X-Tenant-ID' => $tenantB->id,
            'X-Session-ID' => $sessionId,
        ]);

        $response->assertOk()
            ->assertJsonPath('data.tenant_id', $tenantB->id);

        $this->assertDatabaseCount('carts', 2);
    }

    private function createTenant(string $prefix = 'cart'): Tenant
    {
        return Tenant::query()->create([
            'slug' => $prefix.'-'.Str::random(8),
            'name' => ucfirst($prefix).' Tenant',
            'status' => 'active',
            'country' => 'NG',
        ]);
    }
}
