<?php

declare(strict_types=1);

namespace Tests\Feature\Catalog;

use Illuminate\Support\Str;
use Modules\Commerce\Catalog\Models\Product;
use Platform\Billing\Enums\SubscriptionStatus;
use Platform\Billing\Models\Plan;
use Platform\Billing\Models\Subscription;
use Platform\Tenancy\Models\Tenant;
use Tests\Feature\PlatformTestCase;

final class CatalogHealthEndpointTest extends PlatformTestCase
{
    public function test_catalog_health_endpoint_returns_ok(): void
    {
        $response = $this->getJson('/api/v1/commerce/catalog/health');

        $response->assertOk()
            ->assertJson([
                'status' => 'ok',
                'package' => 'catalog',
            ]);
    }
}

final class ProductEndpointTest extends PlatformTestCase
{
    public function test_products_endpoint_returns_only_tenant_products(): void
    {
        $tenant = Tenant::query()->create([
            'slug' => 'catalog-tenant-'.Str::random(8),
            'name' => 'Catalog Tenant',
            'status' => 'active',
            'country' => 'NG',
        ]);

        $otherTenant = Tenant::query()->create([
            'slug' => 'other-tenant-'.Str::random(8),
            'name' => 'Other Tenant',
            'status' => 'active',
            'country' => 'NG',
        ]);

        Product::query()->create([
            'tenant_id' => $tenant->id,
            'name' => 'Tenant Product',
            'slug' => 'tenant-product',
            'price_kobo' => 150_000,
            'status' => 'published',
            'inventory_qty' => 10,
        ]);

        Product::query()->create([
            'tenant_id' => $otherTenant->id,
            'name' => 'Other Product',
            'slug' => 'other-product',
            'price_kobo' => 250_000,
            'status' => 'published',
            'inventory_qty' => 5,
        ]);

        $response = $this->getJson('/api/v1/commerce/catalog/products', [
            'X-Tenant-ID' => $tenant->id,
        ]);

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'Tenant Product')
            ->assertJsonPath('data.0.tenant_id', $tenant->id);
    }

    public function test_products_endpoint_requires_tenant_context(): void
    {
        $response = $this->getJson('/api/v1/commerce/catalog/products');

        $response->assertForbidden()
            ->assertJson([
                'message' => 'Tenant context required.',
            ]);
    }

    public function test_store_product_creates_record_when_entitlement_allows(): void
    {
        $tenant = $this->createTenantWithActiveSubscription();
        $merchant = $this->createMerchantForTenant($tenant);
        $token = $merchant->createToken('test')->plainTextToken;

        $response = $this->postJson('/api/v1/commerce/catalog/products', [
            'name' => 'Ankara Dress',
            'price_kobo' => 2_500_000,
            'status' => 'published',
            'inventory_qty' => 5,
        ], $this->merchantAuthHeaders($tenant->id, $token));

        $response->assertCreated()
            ->assertJsonPath('data.name', 'Ankara Dress')
            ->assertJsonPath('data.tenant_id', $tenant->id);

        $this->assertDatabaseHas('products', [
            'tenant_id' => $tenant->id,
            'name' => 'Ankara Dress',
            'slug' => 'ankara-dress',
        ]);
    }

    public function test_store_product_rejects_when_plan_limit_reached(): void
    {
        $tenant = $this->createTenantWithActiveSubscription('starter');
        $merchant = $this->createMerchantForTenant($tenant);
        $token = $merchant->createToken('test')->plainTextToken;

        for ($i = 0; $i < 100; $i++) {
            Product::query()->create([
                'tenant_id' => $tenant->id,
                'name' => "Product {$i}",
                'slug' => "product-{$i}",
                'price_kobo' => 1_000,
                'status' => 'draft',
                'inventory_qty' => 1,
            ]);
        }

        $response = $this->postJson('/api/v1/commerce/catalog/products', [
            'name' => 'Over Limit Product',
            'price_kobo' => 1_000,
            'status' => 'draft',
            'inventory_qty' => 1,
        ], $this->merchantAuthHeaders($tenant->id, $token));

        $response->assertUnprocessable()
            ->assertJson([
                'message' => 'Product limit reached for current plan.',
            ]);
    }

    private function createTenantWithActiveSubscription(string $planSlug = 'starter'): Tenant
    {
        $tenant = Tenant::query()->create([
            'slug' => 'entitlement-tenant-'.Str::random(8),
            'name' => 'Entitlement Tenant',
            'status' => 'active',
            'country' => 'NG',
        ]);

        $plan = Plan::query()->where('slug', $planSlug)->firstOrFail();

        Subscription::query()->create([
            'tenant_id' => $tenant->id,
            'plan_id' => $plan->id,
            'status' => SubscriptionStatus::Active,
        ]);

        return $tenant;
    }
}
