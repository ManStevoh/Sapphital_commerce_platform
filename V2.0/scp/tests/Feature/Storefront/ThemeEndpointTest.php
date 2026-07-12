<?php

declare(strict_types=1);

namespace Tests\Feature\Storefront;

use Illuminate\Support\Str;
use Platform\Tenancy\Models\Tenant;
use Tests\Feature\PlatformTestCase;

final class ThemeEndpointTest extends PlatformTestCase
{
    public function test_returns_default_scp_dawn_theme_for_tenant(): void
    {
        $tenant = $this->createTenant();

        $response = $this->getJson('/api/v1/commerce/storefront/theme', [
            'X-Tenant-ID' => $tenant->id,
        ]);

        $response->assertOk()
            ->assertJsonPath('data.theme_id', 'scp-dawn')
            ->assertJsonPath('data.id', 'scp-dawn')
            ->assertJsonPath('data.name', 'Lagos Atelier')
            ->assertJsonPath('data.version', '0.1.0')
            ->assertJsonPath('data.settings.primary_color', '#1B4332')
            ->assertJsonPath('data.settings.font_heading', 'Inter')
            ->assertJsonPath('data.settings.logo_url', null)
            ->assertJsonPath('data.market', 'NG');
    }

    public function test_returns_tenant_configured_theme_id(): void
    {
        $tenant = $this->createTenant([
            'settings' => [
                'theme_id' => 'scp-dawn',
                'theme_settings' => [
                    'primary_color' => '#2D6A4F',
                    'logo_url' => 'https://cdn.example.com/logo.png',
                ],
            ],
        ]);

        $response = $this->getJson('/api/v1/commerce/storefront/theme', [
            'X-Tenant-ID' => $tenant->id,
        ]);

        $response->assertOk()
            ->assertJsonPath('data.theme_id', 'scp-dawn')
            ->assertJsonPath('data.settings.primary_color', '#2D6A4F')
            ->assertJsonPath('data.settings.font_heading', 'Inter')
            ->assertJsonPath('data.settings.logo_url', 'https://cdn.example.com/logo.png');
    }

    public function test_theme_endpoint_requires_tenant_context(): void
    {
        $response = $this->getJson('/api/v1/commerce/storefront/theme');

        $response->assertForbidden()
            ->assertJson([
                'message' => 'Tenant context required.',
            ]);
    }

    public function test_theme_endpoint_rejects_unknown_tenant(): void
    {
        $response = $this->getJson('/api/v1/commerce/storefront/theme', [
            'X-Tenant-ID' => (string) Str::uuid(),
        ]);

        $response->assertNotFound()
            ->assertJson([
                'message' => 'Tenant not found.',
            ]);
    }

    public function test_theme_endpoint_rejects_missing_theme_package(): void
    {
        $tenant = $this->createTenant([
            'settings' => [
                'theme_id' => 'does-not-exist',
            ],
        ]);

        $response = $this->getJson('/api/v1/commerce/storefront/theme', [
            'X-Tenant-ID' => $tenant->id,
        ]);

        $response->assertUnprocessable()
            ->assertJson([
                'message' => 'Theme package not found: does-not-exist',
            ]);
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function createTenant(array $overrides = []): Tenant
    {
        return Tenant::query()->create(array_merge([
            'slug' => 'theme-'.Str::random(8),
            'name' => 'Theme Test Tenant',
            'status' => 'active',
            'country' => 'NG',
        ], $overrides));
    }
}
