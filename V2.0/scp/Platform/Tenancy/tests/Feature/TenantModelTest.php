<?php

declare(strict_types=1);

namespace Platform\Tenancy\Tests\Feature;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Orchestra\Testbench\TestCase;
use Platform\Tenancy\Models\Tenant;
use Platform\Tenancy\TenancyServiceProvider;

final class TenantModelTest extends TestCase
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

    public function test_tenant_can_be_created_and_retrieved(): void
    {
        $tenant = Tenant::query()->create([
            'slug' => 'acme-store',
            'name' => 'Acme Store',
            'status' => 'active',
            'country' => 'NG',
        ]);

        $found = Tenant::query()->find($tenant->id);

        $this->assertNotNull($found);
        $this->assertSame('acme-store', $found->slug);
        $this->assertSame('Acme Store', $found->name);
        $this->assertSame('active', $found->status);
        $this->assertSame('NG', $found->country);
    }

    public function test_rls_policy_isolates_tenant_rows_on_postgresql(): void
    {
        if (Schema::getConnection()->getDriverName() !== 'pgsql') {
            $this->markTestSkipped('RLS policies are PostgreSQL-only.');
        }

        $tenantAId = (string) Str::uuid();
        $tenantBId = (string) Str::uuid();

        Tenant::query()->create([
            'id' => $tenantAId,
            'slug' => 'tenant-a',
            'name' => 'Tenant A',
            'status' => 'active',
            'country' => 'NG',
        ]);

        Tenant::query()->create([
            'id' => $tenantBId,
            'slug' => 'tenant-b',
            'name' => 'Tenant B',
            'status' => 'active',
            'country' => 'NG',
        ]);

        DB::statement('SET app.current_tenant_id = ?', [$tenantAId]);

        $visibleIds = Tenant::query()->pluck('id')->all();

        $this->assertSame([$tenantAId], $visibleIds);

        DB::statement('SET app.current_tenant_id = ?', [$tenantBId]);

        $visibleIds = Tenant::query()->pluck('id')->all();

        $this->assertSame([$tenantBId], $visibleIds);
    }
}
