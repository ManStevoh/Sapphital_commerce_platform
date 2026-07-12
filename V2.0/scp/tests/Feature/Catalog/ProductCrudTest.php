<?php

declare(strict_types=1);

namespace Tests\Feature\Catalog;

use Illuminate\Support\Str;
use Modules\Commerce\Catalog\Models\Product;
use Platform\Tenancy\Models\Tenant;
use Tests\Feature\PlatformTestCase;

final class ProductCrudTest extends PlatformTestCase
{
    public function test_show_returns_product(): void
    {
        $tenant = $this->createTenant();

        $product = Product::query()->create([
            'tenant_id' => $tenant->id,
            'name' => 'Show Product',
            'slug' => 'show-product',
            'price_kobo' => 150_000,
            'status' => 'published',
            'inventory_qty' => 10,
        ]);

        $response = $this->getJson("/api/v1/commerce/catalog/products/{$product->id}", [
            'X-Tenant-ID' => $tenant->id,
        ]);

        $response->assertOk()
            ->assertJsonPath('data.id', $product->id)
            ->assertJsonPath('data.name', 'Show Product')
            ->assertJsonPath('data.tenant_id', $tenant->id);
    }

    public function test_store_requires_merchant_authentication(): void
    {
        $tenant = $this->createTenant();

        $response = $this->postJson('/api/v1/commerce/catalog/products', [
            'name' => 'Unauthorized Product',
            'price_kobo' => 100_000,
            'status' => 'draft',
            'inventory_qty' => 1,
        ], [
            'X-Tenant-ID' => $tenant->id,
        ]);

        $response->assertUnauthorized();
    }

    public function test_show_returns_404_for_other_tenants_product(): void
    {
        $tenant = $this->createTenant();
        $otherTenant = $this->createTenant('other');

        $product = Product::query()->create([
            'tenant_id' => $otherTenant->id,
            'name' => 'Other Tenant Product',
            'slug' => 'other-tenant-product',
            'price_kobo' => 250_000,
            'status' => 'published',
            'inventory_qty' => 5,
        ]);

        $response = $this->getJson("/api/v1/commerce/catalog/products/{$product->id}", [
            'X-Tenant-ID' => $tenant->id,
        ]);

        $response->assertNotFound()
            ->assertJson([
                'message' => 'Product not found.',
            ]);
    }

    public function test_update_modifies_fields(): void
    {
        $tenant = $this->createTenant();
        $merchant = $this->createMerchantForTenant($tenant);
        $token = $merchant->createToken('test')->plainTextToken;

        $product = Product::query()->create([
            'tenant_id' => $tenant->id,
            'name' => 'Original Name',
            'slug' => 'original-name',
            'price_kobo' => 100_000,
            'status' => 'draft',
            'inventory_qty' => 3,
        ]);

        $response = $this->putJson("/api/v1/commerce/catalog/products/{$product->id}", [
            'name' => 'Updated Name',
            'slug' => 'updated-name',
            'price_kobo' => 200_000,
            'status' => 'published',
            'inventory_qty' => 12,
        ], $this->merchantAuthHeaders($tenant->id, $token));

        $response->assertOk()
            ->assertJsonPath('data.name', 'Updated Name')
            ->assertJsonPath('data.slug', 'updated-name')
            ->assertJsonPath('data.price_kobo', 200_000)
            ->assertJsonPath('data.status', 'published')
            ->assertJsonPath('data.inventory_qty', 12);

        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'tenant_id' => $tenant->id,
            'name' => 'Updated Name',
            'slug' => 'updated-name',
            'price_kobo' => 200_000,
            'status' => 'published',
            'inventory_qty' => 12,
        ]);
    }

    public function test_delete_removes_product(): void
    {
        $tenant = $this->createTenant();
        $merchant = $this->createMerchantForTenant($tenant);
        $token = $merchant->createToken('test')->plainTextToken;

        $product = Product::query()->create([
            'tenant_id' => $tenant->id,
            'name' => 'Delete Me',
            'slug' => 'delete-me',
            'price_kobo' => 50_000,
            'status' => 'draft',
            'inventory_qty' => 1,
        ]);

        $response = $this->deleteJson(
            "/api/v1/commerce/catalog/products/{$product->id}",
            [],
            $this->merchantAuthHeaders($tenant->id, $token),
        );

        $response->assertNoContent();

        $this->assertDatabaseMissing('products', [
            'id' => $product->id,
        ]);
    }

    private function createTenant(string $prefix = 'catalog'): Tenant
    {
        return Tenant::query()->create([
            'slug' => $prefix.'-tenant-'.Str::random(8),
            'name' => ucfirst($prefix).' Tenant',
            'status' => 'active',
            'country' => 'NG',
        ]);
    }
}
