<?php

declare(strict_types=1);

namespace Tests\Feature\Storefront;

use Tests\Feature\PlatformTestCase;

final class ThemesListTest extends PlatformTestCase
{
    public function test_lists_phase_one_and_phase_two_vertical_themes(): void
    {
        $response = $this->getJson('/api/v1/commerce/storefront/themes');

        $response->assertOk();

        $ids = collect($response->json('data'))->pluck('id')->all();

        $this->assertCount(7, $ids);
        $this->assertContains('scp-dawn', $ids);
        $this->assertContains('scp-market', $ids);
        $this->assertContains('scp-terminal', $ids);
        $this->assertContains('scp-chop-serve', $ids);
        $this->assertContains('scp-studio-pro', $ids);
        $this->assertContains('scp-academy-path', $ids);
        $this->assertContains('scp-launchpad', $ids);
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
                'id' => 'scp-chop-serve',
                'name' => 'Chop & Serve',
                'vertical' => 'food',
            ])
            ->assertJsonFragment([
                'id' => 'scp-studio-pro',
                'name' => 'Studio Pro',
                'vertical' => 'services',
            ])
            ->assertJsonFragment([
                'id' => 'scp-academy-path',
                'name' => 'Academy Path',
                'vertical' => 'education',
            ])
            ->assertJsonFragment([
                'id' => 'scp-launchpad',
                'name' => 'Launchpad',
                'vertical' => 'digital',
            ]);
    }

    public function test_theme_entries_include_color_tokens(): void
    {
        $response = $this->getJson('/api/v1/commerce/storefront/themes');

        $response->assertOk();

        $byId = collect($response->json('data'))->keyBy('id');

        $this->assertSame('#1B4332', $byId['scp-dawn']['colors']['primary']);
        $this->assertSame('#8B4513', $byId['scp-market']['colors']['primary']);
        $this->assertSame('#0D1B2A', $byId['scp-terminal']['colors']['primary']);
        $this->assertSame('#9B2226', $byId['scp-chop-serve']['colors']['primary']);
    }

    public function test_themes_endpoint_does_not_require_tenant_context(): void
    {
        $response = $this->getJson('/api/v1/commerce/storefront/themes');

        $response->assertOk();
    }
}
