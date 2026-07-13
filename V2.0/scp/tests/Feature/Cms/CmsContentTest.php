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

    public function test_published_blog_index_excludes_drafts(): void
    {
        $tenant = $this->createTenant();
        $merchant = $this->createMerchantForTenant($tenant);
        $this->createActiveSubscription($tenant->id);
        $token = $merchant->createToken('cms')->plainTextToken;
        $headers = $this->merchantAuthHeaders($tenant->id, $token);

        $this->postJson('/api/v1/content/cms/blog-posts', [
            'title' => 'Published Post',
            'slug' => 'published-post',
            'author_name' => 'Editor',
            'status' => ContentStatus::Published->value,
            'published_at' => now()->toIso8601String(),
        ], $headers)->assertCreated();

        $this->postJson('/api/v1/content/cms/blog-posts', [
            'title' => 'Draft Post',
            'slug' => 'draft-post',
            'author_name' => 'Editor',
            'status' => ContentStatus::Draft->value,
        ], $headers)->assertCreated();

        $this->getJson('/api/v1/content/cms/blog-posts/published', [
            'X-Tenant-ID' => $tenant->id,
        ])->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.slug', 'published-post');

        $this->getJson('/api/v1/content/cms/blog-posts/by-slug/draft-post', [
            'X-Tenant-ID' => $tenant->id,
        ])->assertNotFound();
    }

    public function test_merchant_can_delete_blog_post(): void
    {
        $tenant = $this->createTenant();
        $merchant = $this->createMerchantForTenant($tenant);
        $this->createActiveSubscription($tenant->id);
        $token = $merchant->createToken('cms')->plainTextToken;
        $headers = $this->merchantAuthHeaders($tenant->id, $token);

        $create = $this->postJson('/api/v1/content/cms/blog-posts', [
            'title' => 'To Delete',
            'slug' => 'to-delete',
            'author_name' => 'Editor',
        ], $headers);

        $postId = (string) $create->json('data.id');

        $this->deleteJson("/api/v1/content/cms/blog-posts/{$postId}", [], $headers)
            ->assertNoContent();

        $this->assertDatabaseMissing('cms_blog_posts', ['id' => $postId]);
    }

    public function test_merchant_can_update_page_with_multi_section_body(): void
    {
        $tenant = $this->createTenant();
        $merchant = $this->createMerchantForTenant($tenant);
        $this->createActiveSubscription($tenant->id);
        $token = $merchant->createToken('cms')->plainTextToken;
        $headers = $this->merchantAuthHeaders($tenant->id, $token);

        $create = $this->postJson('/api/v1/content/cms/pages', [
            'title' => 'Landing',
            'slug' => 'landing',
            'status' => ContentStatus::Draft->value,
        ], $headers)->assertCreated();

        $pageId = (string) $create->json('data.id');

        $bodyJson = [
            'sections' => [
                ['type' => 'rich-text', 'content' => 'Welcome to our shop.'],
                [
                    'type' => 'faq-accordion',
                    'heading' => 'Common questions',
                    'items' => [
                        ['question' => 'Do you ship nationwide?', 'answer' => 'Yes, across Nigeria.'],
                    ],
                ],
            ],
        ];

        $this->putJson("/api/v1/content/cms/pages/{$pageId}", [
            'body_json' => $bodyJson,
            'status' => ContentStatus::Published->value,
            'published_at' => now()->toIso8601String(),
        ], $headers)->assertOk()
            ->assertJsonPath('data.body_json.sections.1.type', 'faq-accordion');

        $this->getJson('/api/v1/content/cms/pages/by-slug/landing', [
            'X-Tenant-ID' => $tenant->id,
        ])->assertOk()
            ->assertJsonPath('data.body_json.sections.0.content', 'Welcome to our shop.');
    }

    public function test_invalid_section_type_is_rejected(): void
    {
        $tenant = $this->createTenant();
        $merchant = $this->createMerchantForTenant($tenant);
        $this->createActiveSubscription($tenant->id);
        $token = $merchant->createToken('cms')->plainTextToken;
        $headers = $this->merchantAuthHeaders($tenant->id, $token);

        $this->postJson('/api/v1/content/cms/pages', [
            'title' => 'Bad Page',
            'slug' => 'bad-page',
            'body_json' => [
                'sections' => [
                    ['type' => 'unknown-widget', 'content' => 'Nope'],
                ],
            ],
        ], $headers)->assertUnprocessable();
    }

    public function test_blog_rss_feed_lists_published_posts(): void
    {
        $tenant = $this->createTenant();
        $merchant = $this->createMerchantForTenant($tenant);
        $this->createActiveSubscription($tenant->id);
        $token = $merchant->createToken('cms')->plainTextToken;
        $headers = $this->merchantAuthHeaders($tenant->id, $token);

        $this->postJson('/api/v1/content/cms/blog-posts', [
            'title' => 'RSS Post',
            'slug' => 'rss-post',
            'author_name' => 'Editor',
            'status' => ContentStatus::Published->value,
            'published_at' => now()->toIso8601String(),
        ], $headers)->assertCreated();

        $this->postJson('/api/v1/content/cms/blog-posts', [
            'title' => 'Draft Only',
            'slug' => 'draft-only-post',
            'author_name' => 'Editor',
            'status' => ContentStatus::Draft->value,
        ], $headers)->assertCreated();

        $response = $this->get('/api/v1/content/cms/blog/feed.xml', [
            'X-Tenant-ID' => $tenant->id,
        ]);

        $response->assertOk()
            ->assertHeader('Content-Type', 'application/rss+xml; charset=UTF-8');

        $xml = (string) $response->getContent();
        $this->assertStringContainsString('RSS Post', $xml);
        $this->assertStringContainsString('/blog/rss-post', $xml);
        $this->assertStringNotContainsString('Draft Only', $xml);
    }

    public function test_merchant_can_update_blog_post_with_sections(): void
    {
        $tenant = $this->createTenant();
        $merchant = $this->createMerchantForTenant($tenant);
        $this->createActiveSubscription($tenant->id);
        $token = $merchant->createToken('cms')->plainTextToken;
        $headers = $this->merchantAuthHeaders($tenant->id, $token);

        $create = $this->postJson('/api/v1/content/cms/blog-posts', [
            'title' => 'Original',
            'slug' => 'original-post',
            'author_name' => 'Editor',
        ], $headers)->assertCreated();

        $postId = (string) $create->json('data.id');

        $this->putJson("/api/v1/content/cms/blog-posts/{$postId}", [
            'title' => 'Updated Title',
            'body_json' => [
                'sections' => [
                    ['type' => 'rich-text', 'content' => 'Full article body.'],
                ],
            ],
            'status' => ContentStatus::Published->value,
            'published_at' => now()->toIso8601String(),
        ], $headers)->assertOk()
            ->assertJsonPath('data.title', 'Updated Title');

        $this->getJson('/api/v1/content/cms/blog-posts/by-slug/original-post', [
            'X-Tenant-ID' => $tenant->id,
        ])->assertOk()
            ->assertJsonPath('data.body_json.sections.0.content', 'Full article body.');
    }

    public function test_published_pages_index_excludes_drafts(): void
    {
        $tenant = $this->createTenant();
        $merchant = $this->createMerchantForTenant($tenant);
        $this->createActiveSubscription($tenant->id);
        $token = $merchant->createToken('cms')->plainTextToken;
        $headers = $this->merchantAuthHeaders($tenant->id, $token);

        $this->postJson('/api/v1/content/cms/pages', [
            'title' => 'Live Page',
            'slug' => 'live-page',
            'status' => ContentStatus::Published->value,
            'published_at' => now()->toIso8601String(),
        ], $headers)->assertCreated();

        $this->postJson('/api/v1/content/cms/pages', [
            'title' => 'Hidden Page',
            'slug' => 'hidden-page',
            'status' => ContentStatus::Draft->value,
        ], $headers)->assertCreated();

        $this->getJson('/api/v1/content/cms/pages/published', [
            'X-Tenant-ID' => $tenant->id,
        ])->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.slug', 'live-page');
    }

    public function test_published_blog_index_is_cursor_paginated(): void
    {
        $tenant = $this->createTenant();
        $merchant = $this->createMerchantForTenant($tenant);
        $this->createActiveSubscription($tenant->id);
        $token = $merchant->createToken('cms')->plainTextToken;
        $headers = $this->merchantAuthHeaders($tenant->id, $token);

        foreach ([1, 2, 3] as $dayOffset) {
            $this->postJson('/api/v1/content/cms/blog-posts', [
                'title' => "Post {$dayOffset}",
                'slug' => "post-{$dayOffset}",
                'author_name' => 'Editor',
                'status' => ContentStatus::Published->value,
                'published_at' => now()->subDays($dayOffset)->toIso8601String(),
            ], $headers)->assertCreated();
        }

        $firstPage = $this->getJson('/api/v1/content/cms/blog-posts/published?limit=2', [
            'X-Tenant-ID' => $tenant->id,
        ])->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.slug', 'post-1')
            ->assertJsonPath('data.1.slug', 'post-2');

        $cursor = (string) $firstPage->json('meta.next_cursor');

        $this->getJson('/api/v1/content/cms/blog-posts/published?limit=2&cursor='.urlencode($cursor), [
            'X-Tenant-ID' => $tenant->id,
        ])->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.slug', 'post-3')
            ->assertJsonPath('meta.next_cursor', null);
    }

    public function test_related_blog_posts_match_tags_and_exclude_current_post(): void
    {
        $tenant = $this->createTenant();
        $merchant = $this->createMerchantForTenant($tenant);
        $this->createActiveSubscription($tenant->id);
        $token = $merchant->createToken('cms')->plainTextToken;
        $headers = $this->merchantAuthHeaders($tenant->id, $token);

        $current = $this->postJson('/api/v1/content/cms/blog-posts', [
            'title' => 'Current',
            'slug' => 'current',
            'author_name' => 'Editor',
            'tags' => ['news'],
            'status' => ContentStatus::Published->value,
            'published_at' => now()->toIso8601String(),
        ], $headers)->assertCreated();

        $this->postJson('/api/v1/content/cms/blog-posts', [
            'title' => 'Related',
            'slug' => 'related',
            'author_name' => 'Editor',
            'tags' => ['news'],
            'status' => ContentStatus::Published->value,
            'published_at' => now()->subDay()->toIso8601String(),
        ], $headers)->assertCreated();

        $this->postJson('/api/v1/content/cms/blog-posts', [
            'title' => 'Unrelated',
            'slug' => 'unrelated',
            'author_name' => 'Editor',
            'tags' => ['ops'],
            'status' => ContentStatus::Published->value,
            'published_at' => now()->subDays(2)->toIso8601String(),
        ], $headers)->assertCreated();

        $currentId = (string) $current->json('data.id');

        $this->getJson("/api/v1/content/cms/blog-posts/{$currentId}/related", [
            'X-Tenant-ID' => $tenant->id,
        ])->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.slug', 'related');
    }

    public function test_merchant_can_save_seo_og_image_and_canonical_url(): void
    {
        $tenant = $this->createTenant();
        $merchant = $this->createMerchantForTenant($tenant);
        $this->createActiveSubscription($tenant->id);
        $token = $merchant->createToken('cms')->plainTextToken;
        $headers = $this->merchantAuthHeaders($tenant->id, $token);

        $create = $this->postJson('/api/v1/content/cms/pages', [
            'title' => 'SEO Page',
            'slug' => 'seo-page',
            'status' => ContentStatus::Published->value,
            'published_at' => now()->toIso8601String(),
        ], $headers)->assertCreated();

        $pageId = (string) $create->json('data.id');

        $this->putJson("/api/v1/content/cms/pages/{$pageId}", [
            'seo_og_image_url' => 'https://cdn.example.test/og-about.jpg',
            'seo_canonical_url' => 'https://store.example.test/pages/seo-page',
        ], $headers)->assertOk()
            ->assertJsonPath('data.seo_og_image_url', 'https://cdn.example.test/og-about.jpg')
            ->assertJsonPath('data.seo_canonical_url', 'https://store.example.test/pages/seo-page');

        $this->getJson('/api/v1/content/cms/pages/by-slug/seo-page', [
            'X-Tenant-ID' => $tenant->id,
        ])->assertOk()
            ->assertJsonPath('data.seo_og_image_url', 'https://cdn.example.test/og-about.jpg')
            ->assertJsonPath('data.seo_canonical_url', 'https://store.example.test/pages/seo-page');
    }

    public function test_merchant_can_schedule_page_and_command_publishes_it(): void
    {
        $tenant = $this->createTenant();
        $merchant = $this->createMerchantForTenant($tenant);
        $this->createActiveSubscription($tenant->id);
        $token = $merchant->createToken('cms')->plainTextToken;
        $headers = $this->merchantAuthHeaders($tenant->id, $token);

        $create = $this->postJson('/api/v1/content/cms/pages', [
            'title' => 'Scheduled Page',
            'slug' => 'scheduled-page',
            'status' => ContentStatus::Scheduled->value,
            'scheduled_publish_at' => now()->addHour()->toIso8601String(),
        ], $headers)->assertCreated()
            ->assertJsonPath('data.status', ContentStatus::Scheduled->value);

        $pageId = (string) $create->json('data.id');

        $this->getJson('/api/v1/content/cms/pages/by-slug/scheduled-page', [
            'X-Tenant-ID' => $tenant->id,
        ])->assertNotFound();

        Page::query()->where('id', $pageId)->update([
            'scheduled_publish_at' => now()->subMinute(),
        ]);

        $this->artisan('cms:process-scheduled-content')->assertSuccessful();

        $this->assertDatabaseHas('cms_pages', [
            'id' => $pageId,
            'status' => ContentStatus::Published->value,
        ]);

        $this->getJson('/api/v1/content/cms/pages/by-slug/scheduled-page', [
            'X-Tenant-ID' => $tenant->id,
        ])->assertOk()
            ->assertJsonPath('data.title', 'Scheduled Page');
    }

    public function test_command_unpublishes_content_past_scheduled_unpublish(): void
    {
        $tenant = $this->createTenant();
        $merchant = $this->createMerchantForTenant($tenant);
        $this->createActiveSubscription($tenant->id);
        $token = $merchant->createToken('cms')->plainTextToken;
        $headers = $this->merchantAuthHeaders($tenant->id, $token);

        $create = $this->postJson('/api/v1/content/cms/blog-posts', [
            'title' => 'Temporary Post',
            'slug' => 'temporary-post',
            'author_name' => 'Editor',
            'status' => ContentStatus::Published->value,
            'published_at' => now()->toIso8601String(),
            'scheduled_unpublish_at' => now()->addHours(2)->toIso8601String(),
        ], $headers)->assertCreated();

        $postId = (string) $create->json('data.id');

        BlogPost::query()->where('id', $postId)->update([
            'scheduled_unpublish_at' => now()->subMinute(),
        ]);

        $this->artisan('cms:process-scheduled-content')->assertSuccessful();

        $this->assertDatabaseHas('cms_blog_posts', [
            'id' => $postId,
            'status' => ContentStatus::Draft->value,
        ]);

        $this->getJson('/api/v1/content/cms/blog-posts/by-slug/temporary-post', [
            'X-Tenant-ID' => $tenant->id,
        ])->assertNotFound();
    }

    public function test_scheduled_status_requires_publish_at(): void
    {
        $tenant = $this->createTenant();
        $merchant = $this->createMerchantForTenant($tenant);
        $this->createActiveSubscription($tenant->id);
        $token = $merchant->createToken('cms')->plainTextToken;
        $headers = $this->merchantAuthHeaders($tenant->id, $token);

        $this->postJson('/api/v1/content/cms/pages', [
            'title' => 'Missing Schedule',
            'slug' => 'missing-schedule',
            'status' => ContentStatus::Scheduled->value,
        ], $headers)->assertUnprocessable();
    }

    public function test_page_update_creates_version_and_restore_works(): void
    {
        $tenant = $this->createTenant();
        $merchant = $this->createMerchantForTenant($tenant);
        $this->createActiveSubscription($tenant->id);
        $token = $merchant->createToken('cms')->plainTextToken;
        $headers = $this->merchantAuthHeaders($tenant->id, $token);

        $create = $this->postJson('/api/v1/content/cms/pages', [
            'title' => 'Versioned Page',
            'slug' => 'versioned-page',
            'body_json' => ['sections' => [['type' => 'rich-text', 'content' => 'Original']]],
            'status' => ContentStatus::Draft->value,
        ], $headers)->assertCreated();

        $pageId = (string) $create->json('data.id');

        $this->putJson("/api/v1/content/cms/pages/{$pageId}", [
            'title' => 'Versioned Page Updated',
            'body_json' => ['sections' => [['type' => 'rich-text', 'content' => 'Updated']]],
        ], $headers)->assertOk();

        $versions = $this->getJson("/api/v1/content/cms/pages/{$pageId}/versions", $headers)
            ->assertOk()
            ->assertJsonCount(1, 'data');

        $versionId = (string) $versions->json('data.0.id');
        $this->assertSame('Original', $versions->json('data.0.snapshot_json.body_json.sections.0.content'));

        $this->postJson("/api/v1/content/cms/pages/{$pageId}/versions/{$versionId}/restore", [], $headers)
            ->assertOk()
            ->assertJsonPath('data.title', 'Versioned Page')
            ->assertJsonPath('data.body_json.sections.0.content', 'Original');

        $this->getJson("/api/v1/content/cms/pages/{$pageId}/versions", $headers)
            ->assertOk()
            ->assertJsonCount(2, 'data');
    }

    public function test_version_history_is_capped_at_ten(): void
    {
        $tenant = $this->createTenant();
        $merchant = $this->createMerchantForTenant($tenant);
        $this->createActiveSubscription($tenant->id);
        $token = $merchant->createToken('cms')->plainTextToken;
        $headers = $this->merchantAuthHeaders($tenant->id, $token);

        $create = $this->postJson('/api/v1/content/cms/pages', [
            'title' => 'Cap Page',
            'slug' => 'cap-page',
            'status' => ContentStatus::Draft->value,
        ], $headers)->assertCreated();

        $pageId = (string) $create->json('data.id');

        for ($i = 1; $i <= 12; $i++) {
            $this->putJson("/api/v1/content/cms/pages/{$pageId}", [
                'title' => "Cap Page {$i}",
            ], $headers)->assertOk();
        }

        $this->getJson("/api/v1/content/cms/pages/{$pageId}/versions", $headers)
            ->assertOk()
            ->assertJsonCount(10, 'data');
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
