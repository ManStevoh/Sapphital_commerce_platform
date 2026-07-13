<?php

declare(strict_types=1);

namespace Tests\Feature\Cms;

use Illuminate\Support\Str;
use Modules\Content\Cms\Enums\ContentStatus;
use Modules\Content\Cms\Models\BlogPost;
use Modules\Content\Cms\Models\NavigationMenu;
use Modules\Content\Cms\Models\Page;
use Platform\Billing\Enums\SubscriptionStatus;
use Platform\Billing\Models\Plan;
use Platform\Billing\Models\Subscription;
use Platform\Tenancy\Models\Tenant;
use Tests\Feature\PlatformTestCase;

final class CmsContentTest extends PlatformTestCase
{
    public function test_cms_health_endpoint_is_public(): void
    {
        $this->getJson('/api/v1/content/cms/health')
            ->assertOk()
            ->assertJsonPath('package', 'cms');
    }

    public function test_merchant_can_create_and_publish_page(): void
    {
        $tenant = $this->createTenant();
        $merchant = $this->createMerchantForTenant($tenant);
        $this->createActiveSubscription($tenant->id);
        $token = $merchant->createToken('cms')->plainTextToken;
        $headers = $this->merchantAuthHeaders($tenant->id, $token);

        $create = $this->postJson('/api/v1/content/cms/pages', [
            'title' => 'About Us',
            'slug' => 'about-us',
            'body_json' => ['sections' => [['type' => 'rich-text', 'content' => 'Welcome']]],
            'seo_title' => 'About Our Shop',
            'status' => ContentStatus::Published->value,
            'published_at' => now()->toIso8601String(),
        ], $headers);

        $create->assertCreated()
            ->assertJsonPath('data.slug', 'about-us')
            ->assertJsonPath('data.status', ContentStatus::Published->value);

        $this->getJson('/api/v1/content/cms/pages/by-slug/about-us', [
            'X-Tenant-ID' => $tenant->id,
        ])->assertOk()
            ->assertJsonPath('data.title', 'About Us');
    }

    public function test_merchant_can_create_blog_post_and_navigation_menu(): void
    {
        $tenant = $this->createTenant();
        $merchant = $this->createMerchantForTenant($tenant);
        $this->createActiveSubscription($tenant->id);
        $token = $merchant->createToken('cms')->plainTextToken;
        $headers = $this->merchantAuthHeaders($tenant->id, $token);

        $this->postJson('/api/v1/content/cms/blog-posts', [
            'title' => 'Launch Day',
            'slug' => 'launch-day',
            'author_name' => 'Store Owner',
            'tags' => ['news'],
            'status' => ContentStatus::Published->value,
            'published_at' => now()->toIso8601String(),
        ], $headers)->assertCreated();

        $this->getJson('/api/v1/content/cms/blog-posts/by-slug/launch-day', [
            'X-Tenant-ID' => $tenant->id,
        ])->assertOk()
            ->assertJsonPath('data.author_name', 'Store Owner');

        $this->putJson('/api/v1/content/cms/navigation/header', [
            'links' => [
                ['label' => 'Home', 'href' => '/', 'open_in_new_tab' => false],
                ['label' => 'About', 'href' => '/about-us'],
            ],
        ], $headers)->assertOk()
            ->assertJsonPath('data.location', 'header');

        $this->getJson('/api/v1/content/cms/navigation/header', [
            'X-Tenant-ID' => $tenant->id,
        ])->assertOk()
            ->assertJsonCount(2, 'data.links');
    }

    public function test_draft_page_is_not_publicly_visible(): void
    {
        $tenant = $this->createTenant();

        Page::query()->create([
            'tenant_id' => $tenant->id,
            'title' => 'Draft Page',
            'slug' => 'draft-only',
            'status' => ContentStatus::Draft,
        ]);

        $this->getJson('/api/v1/content/cms/pages/by-slug/draft-only', [
            'X-Tenant-ID' => $tenant->id,
        ])->assertNotFound();
    }

    private function createTenant(): Tenant
    {
        return Tenant::query()->create([
            'slug' => 'cms-'.Str::random(6),
            'name' => 'CMS Store',
            'status' => 'active',
            'country' => 'NG',
        ]);
    }

    private function createActiveSubscription(string $tenantId): void
    {
        $plan = Plan::query()->where('slug', 'starter')->firstOrFail();

        Subscription::query()->create([
            'tenant_id' => $tenantId,
            'plan_id' => $plan->id,
            'status' => SubscriptionStatus::Active,
        ]);
    }
}
