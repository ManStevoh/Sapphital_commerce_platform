<?php

declare(strict_types=1);

namespace Tests\Feature\Storefront;

use Platform\Identity\Enums\MerchantUserRole;
use Platform\Tenancy\Models\Tenant;
use Tests\Feature\PlatformTestCase;

final class ThemeApplyTest extends PlatformTestCase
{
    public function test_applies_vertical_theme_and_returns_portability_report(): void
    {
        $tenant = Tenant::query()->create([
            'slug' => 'theme-switch',
            'name' => 'Theme Switch',
            'status' => 'active',
            'country' => 'NG',
            'settings' => [
                'theme_id' => 'scp-dawn',
                'theme_settings' => [
                    'primary_color' => '#112233',
                    'font_heading' => 'Inter',
                ],
            ],
        ]);

        $merchant = $this->createMerchantForTenant($tenant, 'owner@theme.test', 'password', MerchantUserRole::Owner);
        $token = $merchant->createToken('test')->plainTextToken;

        $response = $this->putJson('/api/v1/commerce/storefront/theme', [
            'theme_id' => 'scp-chop-serve',
        ], $this->merchantAuthHeaders($tenant->id, $token));

        $response->assertOk()
            ->assertJsonPath('data.theme_id', 'scp-chop-serve')
            ->assertJsonPath('data.vertical', 'food')
            ->assertJsonPath('data.settings.primary_color', '#112233')
            ->assertJsonPath('portability.from_theme_id', 'scp-dawn')
            ->assertJsonPath('portability.to_theme_id', 'scp-chop-serve')
            ->assertJsonPath('portability.retained_content.products', true);

        $this->assertContains('primary_color', $response->json('portability.retained_settings'));

        $tenant->refresh();
        $this->assertSame('scp-chop-serve', $tenant->settings['theme_id'] ?? null);
    }

    public function test_preview_theme_does_not_persist_selection(): void
    {
        $tenant = Tenant::query()->create([
            'slug' => 'theme-preview',
            'name' => 'Theme Preview',
            'status' => 'active',
            'country' => 'NG',
            'settings' => [
                'theme_id' => 'scp-dawn',
            ],
        ]);

        $response = $this->getJson('/api/v1/commerce/storefront/themes/scp-launchpad/preview', [
            'X-Tenant-ID' => $tenant->id,
        ]);

        $response->assertOk()
            ->assertJsonPath('data.theme_id', 'scp-launchpad')
            ->assertJsonPath('data.vertical', 'digital')
            ->assertJsonPath('meta.preview', true)
            ->assertJsonPath('meta.active_theme_id', 'scp-dawn');

        $tenant->refresh();
        $this->assertSame('scp-dawn', $tenant->settings['theme_id'] ?? null);
    }

    public function test_apply_rejects_unknown_theme(): void
    {
        $tenant = Tenant::query()->create([
            'slug' => 'theme-bad',
            'name' => 'Theme Bad',
            'status' => 'active',
            'country' => 'NG',
        ]);

        $merchant = $this->createMerchantForTenant($tenant);
        $token = $merchant->createToken('test')->plainTextToken;

        $this->putJson('/api/v1/commerce/storefront/theme', [
            'theme_id' => 'does-not-exist',
        ], $this->merchantAuthHeaders($tenant->id, $token))
            ->assertUnprocessable();
    }
}
