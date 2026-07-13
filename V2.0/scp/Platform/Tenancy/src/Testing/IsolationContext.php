<?php

declare(strict_types=1);

namespace Platform\Tenancy\Testing;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Platform\Tenancy\Models\Tenant;

final class IsolationContext
{
    /**
     * @return array{0: Tenant, 1: Tenant}
     */
    public static function twoTenants(string $prefix = 'iso'): array
    {
        $alpha = Tenant::query()->create([
            'slug' => $prefix.'-alpha-'.Str::random(6),
            'name' => 'Isolation Alpha',
            'status' => 'active',
            'country' => 'NG',
        ]);

        $beta = Tenant::query()->create([
            'slug' => $prefix.'-beta-'.Str::random(6),
            'name' => 'Isolation Beta',
            'status' => 'active',
            'country' => 'NG',
        ]);

        return [$alpha, $beta];
    }

    public static function setRlsTenant(string $tenantId): void
    {
        $variable = (string) config('tenant-isolation.session_variable', 'app.current_tenant_id');

        DB::statement("SET {$variable} = ?", [$tenantId]);
    }
}
