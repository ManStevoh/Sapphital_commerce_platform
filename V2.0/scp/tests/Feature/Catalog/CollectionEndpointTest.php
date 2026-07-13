<?php

declare(strict_types=1);

namespace Tests\Feature\Catalog;

use Modules\Commerce\Catalog\Enums\CollectionStatus;
use Modules\Commerce\Catalog\Enums\CollectionType;
use Modules\Commerce\Catalog\Models\Collection;
use Modules\Commerce\Catalog\Models\Product;
use Modules\Commerce\Catalog\Services\ProcessScheduledCollectionsService;
use Illuminate\Support\Str;
use Platform\Billing\Enums\SubscriptionStatus;
use Platform\Billing\Models\Plan;
use Platform\Billing\Models\Subscription;
use Platform\Tenancy\Models\Tenant;
use Tests\Feature\PlatformTestCase;

final class CollectionEndpointTest extends PlatformTestCase
{
    public function test_collections_require_tenant_context(): void
    {
        $this->getJson('/api/v1/commerce/catalog/collections')
            ->assertForbidden()
            ->assertJson(['message' => 'Tenant context required.']);
    }

    public function test_merchant_can_create_manual_collection_and_resolve_products(): void
    {
        $tenant = $this->createTenant();
        $merchant = $this->createMerchantForTenant($tenant);
        $this->createActiveSubscription($tenant->id);
        $token = $merchant->createToken('collections')->plainTextToken;
        $headers = $this->merchantAuthHeaders($tenant->id, $token);

        $alpha = Product::query()->create([
            'tenant_id' => $tenant->id,
            'name' => 'Alpha Tee',
            'slug' => 'alpha-tee',
            'price_kobo' => 50_000,
            'status' => 'published',
            'inventory_qty' => 5,
            'tags' => ['tees'],
        ]);

        $beta = Product::query()->create([
            'tenant_id' => $tenant->id,
            'name' => 'Beta Hoodie',
            'slug' => 'beta-hoodie',
            'price_kobo' => 120_000,
            'status' => 'published',
            'inventory_qty' => 3,
            'tags' => ['hoodies'],
        ]);

        $create = $this->postJson('/api/v1/commerce/catalog/collections', [
            'title' => 'Summer Drop',
            'slug' => 'summer-drop',
            'type' => CollectionType::Manual->value,
            'status' => CollectionStatus::Published->value,
            'sort_order' => 'manual',
            'product_ids' => [$beta->id, $alpha->id],
        ], $headers);

        $create->assertCreated()
            ->assertJsonPath('data.slug', 'summer-drop')
            ->assertJsonPath('data.type', 'manual');

        $this->getJson('/api/v1/commerce/catalog/collections/by-slug/summer-drop', [
            'X-Tenant-ID' => $tenant->id,
        ])
            ->assertOk()
            ->assertJsonPath('data.collection.title', 'Summer Drop')
            ->assertJsonCount(2, 'data.products')
            ->assertJsonPath('data.products.0.slug', 'beta-hoodie')
            ->assertJsonPath('data.products.1.slug', 'alpha-tee');
    }

    public function test_smart_on_sale_collection_filters_by_tag(): void
    {
        $tenant = $this->createTenant();
        $merchant = $this->createMerchantForTenant($tenant);
        $this->createActiveSubscription($tenant->id);
        $token = $merchant->createToken('collections')->plainTextToken;
        $headers = $this->merchantAuthHeaders($tenant->id, $token);

        Product::query()->create([
            'tenant_id' => $tenant->id,
            'name' => 'Sale Item',
            'slug' => 'sale-item',
            'price_kobo' => 40_000,
            'status' => 'published',
            'inventory_qty' => 2,
            'tags' => ['sale'],
        ]);

        Product::query()->create([
            'tenant_id' => $tenant->id,
            'name' => 'Regular Item',
            'slug' => 'regular-item',
            'price_kobo' => 60_000,
            'status' => 'published',
            'inventory_qty' => 2,
            'tags' => ['full-price'],
        ]);

        $this->postJson('/api/v1/commerce/catalog/collections', [
            'title' => 'On Sale',
            'slug' => 'on-sale',
            'type' => CollectionType::Smart->value,
            'status' => CollectionStatus::Published->value,
            'sort_order' => 'newest',
            'rules_json' => ['preset' => 'on_sale'],
        ], $headers)->assertCreated();

        $this->getJson('/api/v1/commerce/catalog/collections/by-slug/on-sale', [
            'X-Tenant-ID' => $tenant->id,
        ])
            ->assertOk()
            ->assertJsonCount(1, 'data.products')
            ->assertJsonPath('data.products.0.slug', 'sale-item');
    }

    public function test_smart_new_arrivals_and_rule_price_filter(): void
    {
        $tenant = $this->createTenant();
        $merchant = $this->createMerchantForTenant($tenant);
        $this->createActiveSubscription($tenant->id);
        $token = $merchant->createToken('collections')->plainTextToken;
        $headers = $this->merchantAuthHeaders($tenant->id, $token);

        Product::query()->create([
            'tenant_id' => $tenant->id,
            'name' => 'Cheap',
            'slug' => 'cheap',
            'price_kobo' => 10_000,
            'status' => 'published',
            'inventory_qty' => 1,
            'tags' => ['sale'],
        ]);

        Product::query()->create([
            'tenant_id' => $tenant->id,
            'name' => 'Expensive',
            'slug' => 'expensive',
            'price_kobo' => 900_000,
            'status' => 'published',
            'inventory_qty' => 1,
            'tags' => ['sale'],
        ]);

        $this->postJson('/api/v1/commerce/catalog/collections', [
            'title' => 'Under 100k Sale',
            'slug' => 'under-100k-sale',
            'type' => CollectionType::Smart->value,
            'status' => CollectionStatus::Published->value,
            'rules_json' => [
                'rules' => [
                    ['field' => 'tag', 'operator' => 'eq', 'value' => 'sale'],
                    ['field' => 'price_kobo', 'operator' => 'lte', 'value' => 100_000],
                ],
            ],
        ], $headers)->assertCreated();

        $this->getJson('/api/v1/commerce/catalog/collections/by-slug/under-100k-sale', [
            'X-Tenant-ID' => $tenant->id,
        ])
            ->assertOk()
            ->assertJsonCount(1, 'data.products')
            ->assertJsonPath('data.products.0.slug', 'cheap');
    }

    public function test_scheduled_collection_publishes_and_expires(): void
    {
        $tenant = $this->createTenant();
        $merchant = $this->createMerchantForTenant($tenant);
        $this->createActiveSubscription($tenant->id);
        $token = $merchant->createToken('collections')->plainTextToken;
        $headers = $this->merchantAuthHeaders($tenant->id, $token);

        $create = $this->postJson('/api/v1/commerce/catalog/collections', [
            'title' => 'Flash Sale',
            'slug' => 'flash-sale',
            'type' => CollectionType::Smart->value,
            'status' => CollectionStatus::Scheduled->value,
            'starts_at' => now()->addHour()->toIso8601String(),
            'ends_at' => now()->addDays(2)->toIso8601String(),
            'rules_json' => ['preset' => 'new_arrivals', 'days' => 30],
        ], $headers);

        $create->assertCreated()
            ->assertJsonPath('data.status', CollectionStatus::Scheduled->value);

        $collectionId = $create->json('data.id');

        $this->getJson('/api/v1/commerce/catalog/collections/by-slug/flash-sale', [
            'X-Tenant-ID' => $tenant->id,
        ])->assertNotFound();

        Collection::query()->whereKey($collectionId)->update([
            'starts_at' => now()->subMinute(),
        ]);

        app(ProcessScheduledCollectionsService::class)->run();

        $this->assertSame(
            CollectionStatus::Published,
            Collection::query()->findOrFail($collectionId)->status,
        );

        $this->getJson('/api/v1/commerce/catalog/collections/by-slug/flash-sale', [
            'X-Tenant-ID' => $tenant->id,
        ])->assertOk();

        Collection::query()->whereKey($collectionId)->update([
            'ends_at' => now()->subMinute(),
        ]);

        app(ProcessScheduledCollectionsService::class)->run();

        $this->assertSame(
            CollectionStatus::Draft,
            Collection::query()->findOrFail($collectionId)->status,
        );

        $this->getJson('/api/v1/commerce/catalog/collections/by-slug/flash-sale', [
            'X-Tenant-ID' => $tenant->id,
        ])->assertNotFound();
    }

    public function test_draft_collection_hidden_from_storefront_slug(): void
    {
        $tenant = $this->createTenant();
        $merchant = $this->createMerchantForTenant($tenant);
        $this->createActiveSubscription($tenant->id);
        $token = $merchant->createToken('collections')->plainTextToken;
        $headers = $this->merchantAuthHeaders($tenant->id, $token);

        $this->postJson('/api/v1/commerce/catalog/collections', [
            'title' => 'Hidden',
            'slug' => 'hidden',
            'type' => CollectionType::Manual->value,
            'status' => CollectionStatus::Draft->value,
        ], $headers)->assertCreated();

        $this->getJson('/api/v1/commerce/catalog/collections/by-slug/hidden', [
            'X-Tenant-ID' => $tenant->id,
        ])->assertNotFound();
    }

    private function createTenant(string $prefix = 'collections'): Tenant
    {
        return Tenant::query()->create([
            'slug' => $prefix.'-'.Str::random(8),
            'name' => 'Collections Store',
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
