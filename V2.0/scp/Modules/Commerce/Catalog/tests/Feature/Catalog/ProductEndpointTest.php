<?php

declare(strict_types=1);

namespace Modules\Commerce\Catalog\Tests\Feature\Catalog;

use Illuminate\Support\Str;
use Modules\Commerce\Catalog\CatalogServiceProvider;
use Modules\Commerce\Catalog\Models\Product;
use Orchestra\Testbench\TestCase;
use Platform\Tenancy\Models\Tenant;
use Platform\Tenancy\TenancyServiceProvider;

final class ProductEndpointTest extends TestCase
{
    /**
     * @param  \Illuminate\Foundation\Application  $app
     * @return array<int, class-string>
     */
    protected function getPackageProviders($app): array
    {
        return [
            TenancyServiceProvider::class,
            CatalogServiceProvider::class,
        ];
    }

    protected function defineDatabaseMigrations(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../../../database/migrations');
        $this->loadMigrationsFrom(__DIR__.'/../../../../../../Platform/Tenancy/database/migrations');
    }

    public function test_health_endpoint_returns_ok(): void
    {
        $response = $this->getJson('/api/v1/commerce/catalog/health');

        $response->assertOk()
            ->assertJson([
                'status' => 'ok',
                'package' => 'catalog',
            ]);
    }

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

    public function test_related_products_match_shared_tags(): void
    {
        $tenant = Tenant::query()->create([
            'slug' => 'related-tenant-'.Str::random(8),
            'name' => 'Related Tenant',
            'status' => 'active',
            'country' => 'NG',
        ]);

        $current = Product::query()->create([
            'tenant_id' => $tenant->id,
            'name' => 'Current Phone',
            'slug' => 'current-phone',
            'price_kobo' => 100_000,
            'status' => 'published',
            'inventory_qty' => 3,
            'tags' => ['phones', 'sale'],
        ]);

        Product::query()->create([
            'tenant_id' => $tenant->id,
            'name' => 'Related Phone',
            'slug' => 'related-phone',
            'price_kobo' => 120_000,
            'status' => 'published',
            'inventory_qty' => 2,
            'tags' => ['phones'],
        ]);

        Product::query()->create([
            'tenant_id' => $tenant->id,
            'name' => 'Unrelated Item',
            'slug' => 'unrelated-item',
            'price_kobo' => 50_000,
            'status' => 'published',
            'inventory_qty' => 1,
            'tags' => ['fashion'],
        ]);

        $this->getJson('/api/v1/commerce/catalog/products/'.$current->id.'/related', [
            'X-Tenant-ID' => $tenant->id,
        ])->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.slug', 'related-phone');
    }
}
