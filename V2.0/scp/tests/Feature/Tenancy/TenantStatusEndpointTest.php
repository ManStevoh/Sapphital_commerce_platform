<?php

declare(strict_types=1);

namespace Tests\Feature\Tenancy;

use Platform\Tenancy\Models\Tenant;
use Tests\Feature\PlatformTestCase;

final class TenantStatusEndpointTest extends PlatformTestCase
{
    public function test_by_slug_returns_tenant_status(): void
    {
        $tenant = Tenant::query()->create([
            'slug' => 'status-check-shop',
            'name' => 'Status Check Shop',
            'status' => 'active',
            'country' => 'NG',
        ]);

        $this->getJson('/api/v1/platform/tenancy/tenants/by-slug/status-check-shop')
            ->assertOk()
            ->assertJsonPath('id', $tenant->id)
            ->assertJsonPath('status', 'active');
    }

    public function test_by_slug_returns_404_for_unknown_tenant(): void
    {
        $this->getJson('/api/v1/platform/tenancy/tenants/by-slug/does-not-exist-shop')
            ->assertNotFound();
    }

    public function test_suspended_tenant_status_is_exposed_for_storefront_gate(): void
    {
        Tenant::query()->create([
            'slug' => 'suspended-shop',
            'name' => 'Suspended Shop',
            'status' => 'suspended',
            'country' => 'NG',
        ]);

        $this->getJson('/api/v1/platform/tenancy/tenants/by-slug/suspended-shop')
            ->assertOk()
            ->assertJsonPath('status', 'suspended');
    }
}
