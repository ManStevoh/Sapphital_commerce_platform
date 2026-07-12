<?php

declare(strict_types=1);

namespace Platform\Tenancy\Tests\Feature;

use Illuminate\Support\Str;
use Orchestra\Testbench\TestCase;
use Platform\Tenancy\Middleware\SetTenantContext;
use Platform\Tenancy\Models\Tenant;
use Platform\Tenancy\TenancyServiceProvider;

final class SetTenantContextTest extends TestCase
{
    /**
     * @param  \Illuminate\Foundation\Application  $app
     * @return array<int, class-string>
     */
    protected function getPackageProviders($app): array
    {
        return [TenancyServiceProvider::class];
    }

    protected function defineDatabaseMigrations(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../../database/migrations');
    }

    public function test_middleware_sets_tenant_context_from_header(): void
    {
        $tenant = Tenant::query()->create([
            'slug' => 'header-tenant-'.Str::random(8),
            'name' => 'Header Tenant',
            'status' => 'active',
            'country' => 'NG',
        ]);

        $middleware = new SetTenantContext;

        $request = $this->app['request']->create(
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

        $request = $this->app['request']->create(
            "https://{$slug}.sapphital.test/api/v1/example",
            'GET',
        );

        $response = $middleware->handle($request, fn () => response()->noContent());

        $this->assertSame(204, $response->getStatusCode());
        $this->assertSame($tenant->id, $request->attributes->get('tenant_id'));
    }
}
