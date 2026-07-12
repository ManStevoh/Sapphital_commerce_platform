<?php

declare(strict_types=1);

namespace Tests\Isolation;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Modules\Commerce\Catalog\Models\Product;
use Platform\Identity\Models\MerchantUser;
use Platform\Tenancy\Models\Tenant;
use Tests\Feature\PlatformTestCase;

/**
 * Cross-tenant isolation matrix — NFR-040 / Vol 13 Ch. 04.
 */
final class CrossTenantIsolationTest extends PlatformTestCase
{
    public function test_manifest_lists_all_tenant_scoped_models(): void
    {
        $manifest = config('tenant-isolation.models', []);

        $this->assertCount(11, $manifest);
        $this->assertContains(Product::class, $manifest);
        $this->assertContains(MerchantUser::class, $manifest);
    }

    public function test_merchant_cannot_access_other_tenant_with_mismatched_header(): void
    {
        $tenantA = $this->createTenant('tenant-a');
        $tenantB = $this->createTenant('tenant-b');

        $merchant = $this->createMerchantForTenant($tenantA);
        $token = $merchant->createToken('test')->plainTextToken;

        Product::query()->create([
            'tenant_id' => $tenantB->id,
            'name' => 'Tenant B Product',
            'slug' => 'tenant-b-product',
            'price_kobo' => 100_000,
            'status' => 'published',
            'inventory_qty' => 1,
        ]);

        $response = $this->postJson('/api/v1/commerce/catalog/products', [
            'name' => 'Blocked Product',
            'price_kobo' => 50_000,
            'status' => 'draft',
            'inventory_qty' => 1,
        ], $this->merchantAuthHeaders($tenantB->id, $token));

        $response->assertForbidden();
    }

    public function test_rls_isolates_products_on_postgresql(): void
    {
        if (Schema::getConnection()->getDriverName() !== 'pgsql') {
            $this->markTestSkipped('RLS policies are PostgreSQL-only.');
        }

        $tenantA = $this->createTenant('rls-a');
        $tenantB = $this->createTenant('rls-b');

        Product::query()->create([
            'tenant_id' => $tenantA->id,
            'name' => 'Product A',
            'slug' => 'product-a',
            'price_kobo' => 100_000,
            'status' => 'published',
            'inventory_qty' => 1,
        ]);

        Product::query()->create([
            'tenant_id' => $tenantB->id,
            'name' => 'Product B',
            'slug' => 'product-b',
            'price_kobo' => 200_000,
            'status' => 'published',
            'inventory_qty' => 1,
        ]);

        DB::statement('SET app.current_tenant_id = ?', [$tenantA->id]);

        $visible = Product::query()->pluck('tenant_id')->unique()->values()->all();

        $this->assertSame([$tenantA->id], $visible);

        DB::statement('SET app.current_tenant_id = ?', [$tenantB->id]);

        $visible = Product::query()->pluck('tenant_id')->unique()->values()->all();

        $this->assertSame([$tenantB->id], $visible);
    }

    private function createTenant(string $prefix): Tenant
    {
        return Tenant::query()->create([
            'slug' => $prefix.'-'.Str::random(6),
            'name' => ucfirst($prefix).' Tenant',
            'status' => 'active',
            'country' => 'NG',
        ]);
    }
}
