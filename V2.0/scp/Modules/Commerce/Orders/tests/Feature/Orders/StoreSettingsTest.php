<?php

declare(strict_types=1);

namespace Modules\Commerce\Orders\Tests\Feature\Orders;

use Illuminate\Support\Str;
use Platform\Billing\Enums\SubscriptionStatus;
use Platform\Billing\Models\Plan;
use Platform\Billing\Models\Subscription;
use Platform\Tenancy\Models\Tenant;
use Tests\Feature\PlatformTestCase;

final class StoreSettingsTest extends PlatformTestCase
{
    public function test_merchant_can_view_and_update_return_window(): void
    {
        $tenant = Tenant::query()->create([
            'slug' => 'settings-'.Str::random(6),
            'name' => 'Settings Tenant',
            'status' => 'active',
            'country' => 'NG',
            'settings' => ['return_window_days' => 14],
        ]);

        $merchant = $this->createMerchantForTenant($tenant, 'settings@test.com', 'password12345');
        $this->createActiveSubscription($tenant->id);
        $token = $this->loginMerchant($merchant->email, 'password12345');
        $headers = $this->merchantAuthHeaders($tenant->id, $token);

        $this->getJson('/api/v1/commerce/storefront/settings', $headers)
            ->assertOk()
            ->assertJsonPath('data.return_window_days', 14);

        $this->putJson('/api/v1/commerce/storefront/settings/returns', [
            'return_window_days' => 21,
        ], $headers)->assertOk()
            ->assertJsonPath('data.return_window_days', 21);

        $tenant->refresh();
        $this->assertSame(21, $tenant->settings['return_window_days'] ?? null);
    }

    public function test_return_window_is_clamped_to_allowed_range(): void
    {
        $tenant = Tenant::query()->create([
            'slug' => 'clamp-'.Str::random(6),
            'name' => 'Clamp Tenant',
            'status' => 'active',
            'country' => 'NG',
        ]);

        $merchant = $this->createMerchantForTenant($tenant, 'clamp@test.com', 'password12345');
        $this->createActiveSubscription($tenant->id);
        $token = $this->loginMerchant($merchant->email, 'password12345');
        $headers = $this->merchantAuthHeaders($tenant->id, $token);

        $this->putJson('/api/v1/commerce/storefront/settings/returns', [
            'return_window_days' => 45,
        ], $headers)->assertOk()
            ->assertJsonPath('data.return_window_days', 30);
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

    private function loginMerchant(string $email, string $password): string
    {
        $login = $this->postJson('/api/v1/auth/merchant/login', [
            'email' => $email,
            'password' => $password,
        ]);

        return (string) $login->json('token');
    }
}
