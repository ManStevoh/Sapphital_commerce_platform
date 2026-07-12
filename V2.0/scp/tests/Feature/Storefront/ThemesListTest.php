<?php

declare(strict_types=1);

namespace Tests\Feature\Storefront;

use Tests\Feature\PlatformTestCase;

final class ThemesListTest extends PlatformTestCase
{
    public function test_lists_all_available_phase_one_themes(): void
    {
        $response = $this->getJson('/api/v1/commerce/storefront/themes');

        $response->assertOk();

        $ids = collect($response->json('data'))->pluck('id')->all();

        $this->assertCount(3, $ids);
        $this->assertContains('scp-dawn', $ids);
        $this->assertContains('scp-market', $ids);
        $this->assertContains('scp-terminal', $ids);
    }

    public function test_theme_entries_include_manifest_metadata(): void
    {
        $response = $this->getJson('/api/v1/commerce/storefront/themes');

        $response->assertOk()
            ->assertJsonFragment([
                'id' => 'scp-dawn',
                'name' => 'Lagos Atelier',
                'version' => '0.1.0',
            ])
            ->assertJsonFragment([
                'id' => 'scp-market',
                'name' => 'Savanna Market',
                'version' => '0.1.0',
            ])
            ->assertJsonFragment([
                'id' => 'scp-terminal',
                'name' => 'Terminal Tech',
                'version' => '0.1.0',
            ]);
    }

    public function test_theme_entries_include_color_tokens(): void
    {
        $response = $this->getJson('/api/v1/commerce/storefront/themes');

        $response->assertOk()
            ->assertJsonPath('data.0.colors.primary', '#1B4332')
            ->assertJsonPath('data.1.colors.primary', '#8B4513')
            ->assertJsonPath('data.2.colors.primary', '#0D1B2A');
    }

    public function test_themes_endpoint_does_not_require_tenant_context(): void
    {
        $response = $this->getJson('/api/v1/commerce/storefront/themes');

        $response->assertOk();
    }
}
