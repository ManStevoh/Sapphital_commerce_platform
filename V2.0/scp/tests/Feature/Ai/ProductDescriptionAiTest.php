<?php

declare(strict_types=1);

namespace Tests\Feature\Ai;

use Illuminate\Support\Str;
use Platform\Ai\Services\PiiScrubber;
use Platform\Billing\Enums\SubscriptionStatus;
use Platform\Billing\Models\Plan;
use Platform\Billing\Models\Subscription;
use Platform\Tenancy\Models\Tenant;
use Tests\Feature\PlatformTestCase;

final class ProductDescriptionAiTest extends PlatformTestCase
{
    public function test_merchant_can_generate_product_description_draft(): void
    {
        $tenant = $this->createTenant();
        $merchant = $this->createMerchantForTenant($tenant);
        $this->createActiveSubscription($tenant->id);
        $token = $merchant->createToken('ai')->plainTextToken;
        $headers = $this->merchantAuthHeaders($tenant->id, $token);

        $response = $this->postJson('/api/v1/platform/ai/product-description', [
            'title' => 'Ankara Midi Dress',
            'keywords' => ['ankara', 'dress', 'contact me at leak@example.com'],
        ], $headers);

        $response->assertOk()
            ->assertJsonPath('data.requires_merchant_edit', true)
            ->assertJsonPath('data.watermark', 'ai-generated-draft')
            ->assertJsonPath('data.provider', 'fake');

        $draft = (string) $response->json('data.draft');
        $this->assertStringContainsString('[AI draft]', $draft);
        $this->assertStringNotContainsString('leak@example.com', $draft);

        $this->assertDatabaseHas('ai_usage_events', [
            'tenant_id' => $tenant->id,
            'feature_key' => 'product_description',
            'was_watermarked' => 1,
        ]);

        $this->getJson('/api/v1/platform/ai/usage', $headers)
            ->assertOk()
            ->assertJsonPath('data.requests', 1);
    }

    public function test_merchant_can_generate_seo_meta_draft(): void
    {
        $tenant = $this->createTenant();
        $merchant = $this->createMerchantForTenant($tenant);
        $this->createActiveSubscription($tenant->id);
        $token = $merchant->createToken('ai')->plainTextToken;
        $headers = $this->merchantAuthHeaders($tenant->id, $token);

        $this->postJson('/api/v1/platform/ai/seo-meta', [
            'title' => 'Ankara Midi Dress',
            'content' => 'Cotton ankara dress for casual wear',
        ], $headers)
            ->assertOk()
            ->assertJsonPath('data.requires_merchant_edit', true)
            ->assertJsonPath('data.provider', 'fake');
    }

    public function test_opt_out_blocks_generation(): void
    {
        $tenant = $this->createTenant(['ai_enabled' => false]);
        $merchant = $this->createMerchantForTenant($tenant);
        $this->createActiveSubscription($tenant->id);
        $token = $merchant->createToken('ai')->plainTextToken;
        $headers = $this->merchantAuthHeaders($tenant->id, $token);

        $this->postJson('/api/v1/platform/ai/product-description', [
            'title' => 'Blocked',
            'keywords' => ['x', 'y', 'z'],
        ], $headers)->assertForbidden();
    }

    public function test_daily_limit_enforced(): void
    {
        $tenant = $this->createTenant();
        $merchant = $this->createMerchantForTenant($tenant);
        $this->createActiveSubscription($tenant->id);
        $token = $merchant->createToken('ai')->plainTextToken;
        $headers = $this->merchantAuthHeaders($tenant->id, $token);

        config(['ai.daily_limits.starter' => 1]);

        $this->postJson('/api/v1/platform/ai/product-description', [
            'title' => 'First',
            'keywords' => ['a', 'b', 'c'],
        ], $headers)->assertOk();

        $this->postJson('/api/v1/platform/ai/product-description', [
            'title' => 'Second',
            'keywords' => ['a', 'b', 'c'],
        ], $headers)->assertStatus(429);
    }

    public function test_pii_scrubber_redacts_email_and_phone(): void
    {
        $scrubber = new PiiScrubber;

        $this->assertSame(
            'Call [redacted-phone] or email [redacted-email]',
            $scrubber->scrub('Call 08031234567 or email shop@example.com'),
        );
    }

    /**
     * @param  array<string, mixed>  $settings
     */
    private function createTenant(array $settings = []): Tenant
    {
        return Tenant::query()->create([
            'slug' => 'ai-'.Str::random(8),
            'name' => 'AI Store',
            'status' => 'active',
            'country' => 'NG',
            'settings' => $settings,
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
