<?php

declare(strict_types=1);

namespace Tests\Feature\Tenancy;

use Illuminate\Support\Str;
use Platform\Tenancy\Models\CustomDomain;
use Platform\Tenancy\Models\Tenant;
use Platform\Tenancy\Support\TenantContext;
use Tests\Feature\PlatformTestCase;

final class CustomDomainSchemaTest extends PlatformTestCase
{
    public function test_custom_domain_row_persists_with_verification_token(): void
    {
        $tenant = Tenant::query()->create([
            'slug' => 'domain-'.Str::random(6),
            'name' => 'Domain Store',
            'status' => 'active',
            'country' => 'NG',
        ]);

        TenantContext::set($tenant->id);

        $domain = CustomDomain::query()->create([
            'tenant_id' => $tenant->id,
            'domain' => 'shop.example.ng',
            'is_primary' => true,
            'verification_token' => 'verify-token-123',
            'status' => 'pending',
        ]);

        $this->assertDatabaseHas('custom_domains', [
            'id' => $domain->id,
            'tenant_id' => $tenant->id,
            'domain' => 'shop.example.ng',
            'status' => 'pending',
        ]);
    }
}
