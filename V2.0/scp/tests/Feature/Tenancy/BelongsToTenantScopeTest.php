<?php

declare(strict_types=1);

namespace Tests\Feature\Tenancy;

use Illuminate\Support\Str;
use Modules\Commerce\Catalog\Models\Product;
use Platform\Tenancy\Models\Tenant;
use Platform\Tenancy\Support\TenantContext;
use Tests\Feature\PlatformTestCase;

final class BelongsToTenantScopeTest extends PlatformTestCase
{
    protected function tearDown(): void
    {
        TenantContext::clear();

        parent::tearDown();
    }

    public function test_global_scope_hides_other_tenant_rows_when_context_set(): void
    {
        $alpha = $this->createTenant('scope-alpha');
        $beta = $this->createTenant('scope-beta');

        $betaProduct = Product::query()->create([
            'tenant_id' => $beta->id,
            'name' => 'Scoped Beta Product',
            'slug' => 'scoped-beta-product',
            'price_kobo' => 100_000,
            'status' => 'published',
            'inventory_qty' => 1,
        ]);

        TenantContext::set($alpha->id);

        $this->assertNull(Product::query()->find($betaProduct->id));
        $this->assertSame([], Product::query()->pluck('id')->all());
    }

    public function test_global_scope_is_inactive_without_context(): void
    {
        $beta = $this->createTenant('scope-open');

        Product::query()->create([
            'tenant_id' => $beta->id,
            'name' => 'Open Scope Product',
            'slug' => 'open-scope-product',
            'price_kobo' => 50_000,
            'status' => 'published',
            'inventory_qty' => 1,
        ]);

        $this->assertGreaterThanOrEqual(1, Product::query()->count());
    }

    public function test_creating_auto_assigns_tenant_id_from_context(): void
    {
        $tenant = $this->createTenant('scope-create');

        TenantContext::set($tenant->id);

        $product = Product::query()->create([
            'name' => 'Auto Tenant Product',
            'slug' => 'auto-tenant-product',
            'price_kobo' => 75_000,
            'status' => 'draft',
            'inventory_qty' => 2,
        ]);

        $this->assertSame($tenant->id, $product->tenant_id);
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
