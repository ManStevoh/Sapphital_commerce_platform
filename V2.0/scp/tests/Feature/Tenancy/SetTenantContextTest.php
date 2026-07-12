<?php

declare(strict_types=1);

namespace Tests\Feature\Tenancy;

use Illuminate\Support\Str;
use Platform\Tenancy\Middleware\SetTenantContext;
use Platform\Tenancy\Models\Tenant;
use Tests\Feature\PlatformTestCase;

final class SetTenantContextTest extends PlatformTestCase
{
    public function test_middleware_sets_tenant_context_from_header(): void
    {
        $tenant = Tenant::query()->create([
            'slug' => 'header-tenant-'.strtolower(Str::random(8)),
            'name' => 'Header Tenant',
            'status' => 'active',
            'country' => 'NG',
        ]);

        $middleware = new SetTenantContext;

        $request = request()->create(
            '/api/v1/example',
            'GET',
            server: ['HTTP_X_TENANT_ID' => $tenant->id],
        );

        $response = $middleware->handle($request, fn () => response()->noContent());

        $this->assertSame(204, $response->getStatusCode());
        $this->assertSame($tenant->id, $request->attributes->get('tenant_id'));
    }

    public function test_middleware_resolves_tenant_from_subdomain(): void
    {
        $slug = 'acme-'.strtolower(Str::random(8));

        $tenant = Tenant::query()->create([
            'slug' => $slug,
            'name' => 'Acme',
            'status' => 'active',
            'country' => 'NG',
        ]);

        $middleware = new SetTenantContext;

        $request = request()->create(
            "https://{$slug}.shops.sapphital.test/api/v1/example",
            'GET',
        );

        $response = $middleware->handle($request, fn () => response()->noContent());

        $this->assertSame(204, $response->getStatusCode());
        $this->assertSame($tenant->id, $request->attributes->get('tenant_id'));
    }

    public function test_catalog_products_work_with_tenant_header(): void
    {
        $tenant = Tenant::query()->create([
            'slug' => 'catalog-route-'.strtolower(Str::random(8)),
            'name' => 'Catalog Route Tenant',
            'status' => 'active',
            'country' => 'NG',
        ]);

        $this->getJson('/api/v1/commerce/catalog/products', [
            'X-Tenant-ID' => $tenant->id,
        ])->assertOk()
            ->assertJsonCount(0, 'data');
    }
}
