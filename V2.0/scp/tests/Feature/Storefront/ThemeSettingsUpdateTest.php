<?php

declare(strict_types=1);

namespace Tests\Feature\Storefront;

use Illuminate\Support\Str;
use Platform\Tenancy\Models\Tenant;
use Tests\Feature\PlatformTestCase;

final class ThemeSettingsUpdateTest extends PlatformTestCase
{
    public function test_merchant_can_update_theme_settings(): void
    {
        $tenant = Tenant::query()->create([
            'slug' => 'theme-settings-'.Str::random(6),
            'name' => 'Theme Settings Shop',
            'status' => 'active',
            'country' => 'NG',
        ]);

        $merchant = $this->createMerchantForTenant($tenant);
        $token = $merchant->createToken('test')->plainTextToken;

        $response = $this->putJson(
            '/api/v1/commerce/storefront/theme/settings',
            [
                'primary_color' => '#112233',
                'font_heading' => 'Poppins',
                'logo_url' => 'https://cdn.example.com/logo.png',
            ],
            $this->merchantAuthHeaders($tenant->id, $token),
        );

        $response->assertOk()
            ->assertJsonPath('data.settings.primary_color', '#112233')
            ->assertJsonPath('data.settings.font_heading', 'Poppins')
            ->assertJsonPath('data.settings.logo_url', 'https://cdn.example.com/logo.png');

        $tenant->refresh();

        $this->assertSame('#112233', $tenant->settings['theme_settings']['primary_color'] ?? null);
    }

    public function test_theme_settings_update_requires_merchant_auth(): void
    {
        $tenant = Tenant::query()->create([
            'slug' => 'theme-auth-'.Str::random(6),
            'name' => 'Theme Auth Shop',
            'status' => 'active',
            'country' => 'NG',
        ]);

        $this->putJson(
            '/api/v1/commerce/storefront/theme/settings',
            ['primary_color' => '#000000'],
            ['X-Tenant-ID' => $tenant->id],
        )->assertUnauthorized();
    }
}
