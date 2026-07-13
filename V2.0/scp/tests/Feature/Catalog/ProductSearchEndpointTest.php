<?php

declare(strict_types=1);

namespace Tests\Feature\Catalog;

use Illuminate\Support\Str;
use Modules\Commerce\Catalog\Models\Product;
use Modules\Commerce\Catalog\Models\ProductSearchQuery;
use Modules\Commerce\Catalog\Models\ProductSearchSynonym;
use Platform\Billing\Enums\SubscriptionStatus;
use Platform\Billing\Models\Plan;
use Platform\Billing\Models\Subscription;
use Platform\Tenancy\Models\Tenant;
use Tests\Feature\PlatformTestCase;

final class ProductSearchEndpointTest extends PlatformTestCase
{
    public function test_search_requires_tenant_context(): void
    {
        $this->getJson('/api/v1/commerce/catalog/search?q=shirt')
            ->assertForbidden();
    }

    public function test_search_filters_by_text_facets_and_records_analytics(): void
    {
        $tenant = $this->createTenant();

        Product::query()->create([
            'tenant_id' => $tenant->id,
            'name' => 'Blue Shirt',
            'slug' => 'blue-shirt',
            'price_kobo' => 40_000,
            'status' => 'published',
            'inventory_qty' => 4,
            'fulfillment_type' => 'physical',
            'tags' => ['apparel'],
        ]);

        Product::query()->create([
            'tenant_id' => $tenant->id,
            'name' => 'Ebook Guide',
            'slug' => 'ebook-guide',
            'price_kobo' => 15_000,
            'status' => 'published',
            'inventory_qty' => 100,
            'fulfillment_type' => 'digital',
            'tags' => ['digital'],
        ]);

        Product::query()->create([
            'tenant_id' => $tenant->id,
            'name' => 'Draft Shirt',
            'slug' => 'draft-shirt',
            'price_kobo' => 20_000,
            'status' => 'draft',
            'inventory_qty' => 1,
        ]);

        $response = $this->getJson(
            '/api/v1/commerce/catalog/search?q=shirt&min_price_kobo=10000&max_price_kobo=50000&in_stock=1&fulfillment_type=physical',
            ['X-Tenant-ID' => $tenant->id],
        );

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.slug', 'blue-shirt')
            ->assertJsonPath('meta.results_count', 1)
            ->assertJsonStructure([
                'meta' => [
                    'facets' => [
                        'price' => ['min_kobo', 'max_kobo'],
                        'availability' => ['in_stock', 'out_of_stock'],
                        'fulfillment_type',
                    ],
                ],
            ]);

        $this->assertDatabaseHas('product_search_queries', [
            'tenant_id' => $tenant->id,
            'query' => 'shirt',
            'results_count' => 1,
        ]);
    }

    public function test_platform_and_custom_synonyms_expand_search(): void
    {
        $tenant = $this->createTenant();
        $merchant = $this->createMerchantForTenant($tenant);
        $this->createActiveSubscription($tenant->id);
        $token = $merchant->createToken('search')->plainTextToken;
        $headers = $this->merchantAuthHeaders($tenant->id, $token);

        Product::query()->create([
            'tenant_id' => $tenant->id,
            'name' => 'City Trainers',
            'slug' => 'city-trainers',
            'price_kobo' => 80_000,
            'status' => 'published',
            'inventory_qty' => 2,
            'tags' => ['trainers'],
        ]);

        $this->getJson('/api/v1/commerce/catalog/search?q=sneakers', [
            'X-Tenant-ID' => $tenant->id,
        ])
            ->assertOk()
            ->assertJsonPath('data.0.slug', 'city-trainers');

        $this->postJson('/api/v1/commerce/catalog/search/synonyms', [
            'term' => 'anka',
            'synonym' => 'dress',
        ], $headers)->assertCreated();

        Product::query()->create([
            'tenant_id' => $tenant->id,
            'name' => 'Ankara Dress',
            'slug' => 'ankara-dress',
            'price_kobo' => 55_000,
            'status' => 'published',
            'inventory_qty' => 3,
            'tags' => ['dress'],
        ]);

        $this->getJson('/api/v1/commerce/catalog/search?q=anka', [
            'X-Tenant-ID' => $tenant->id,
        ])
            ->assertOk()
            ->assertJsonPath('data.0.slug', 'ankara-dress');

        $analytics = $this->getJson('/api/v1/commerce/catalog/search/analytics', $headers);
        $analytics->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'top_queries',
                    'zero_result_queries',
                    'window_days',
                ],
            ]);
    }

    public function test_zero_result_query_is_recorded(): void
    {
        $tenant = $this->createTenant();

        $this->getJson('/api/v1/commerce/catalog/search?q=zzzz-missing', [
            'X-Tenant-ID' => $tenant->id,
        ])
            ->assertOk()
            ->assertJsonCount(0, 'data')
            ->assertJsonPath('meta.results_count', 0);

        $this->assertTrue(
            ProductSearchQuery::query()
                ->where('tenant_id', $tenant->id)
                ->where('query', 'zzzz-missing')
                ->where('results_count', 0)
                ->exists(),
        );

        $this->assertSame(0, ProductSearchSynonym::query()->where('tenant_id', $tenant->id)->count());
    }

    private function createTenant(string $prefix = 'search'): Tenant
    {
        return Tenant::query()->create([
            'slug' => $prefix.'-'.Str::random(8),
            'name' => 'Search Store',
            'status' => 'active',
            'country' => 'NG',
        ]);
    }

    private function createActiveSubscription(string $tenantId): void
    {
        $plan = Plan::query()->where('slug', 'starter')->firstOrFail();

        Subscription::query()->create([
            'tenant_id' => $tenantId,
            'plan_id' => $plan->id,
            'status' => SubscriptionStatus::Active,
        ]);
    }
}
