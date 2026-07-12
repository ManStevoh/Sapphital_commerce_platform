<?php

declare(strict_types=1);

namespace Tests\Feature\Tenancy;

use Illuminate\Support\Str;
use Platform\Tenancy\Models\Tenant;
use Tests\Feature\PlatformTestCase;

final class TenantBySlugTest extends PlatformTestCase
{
    public function test_returns_tenant_by_slug(): void
    {
        $slug = 'acme-'.strtolower(Str::random(8));

        $tenant = Tenant::query()->create([
            'slug' => $slug,
            'name' => 'Acme Store',
            'status' => 'active',
            'country' => 'NG',
        ]);

        $response = $this->getJson("/api/v1/platform/tenancy/tenants/by-slug/{$slug}");

        $response->assertOk()
            ->assertJson([
                'id' => $tenant->id,
                'slug' => $slug,
                'name' => 'Acme Store',
                'status' => 'active',
            ]);
    }

    public function test_returns_404_for_unknown_slug(): void
    {
        $response = $this->getJson('/api/v1/platform/tenancy/tenants/by-slug/does-not-exist');

        $response->assertNotFound()
            ->assertJson([
                'message' => 'Tenant not found.',
            ]);
    }

    public function test_slug_lookup_is_case_insensitive(): void
    {
        $slug = 'lagos-tech-'.strtolower(Str::random(8));

        $tenant = Tenant::query()->create([
            'slug' => $slug,
            'name' => 'Lagos Tech',
            'status' => 'active',
            'country' => 'NG',
        ]);

        $response = $this->getJson('/api/v1/platform/tenancy/tenants/by-slug/'.strtoupper($slug));

        $response->assertOk()
            ->assertJsonPath('id', $tenant->id)
            ->assertJsonPath('slug', $slug);
    }
}
