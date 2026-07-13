<?php

declare(strict_types=1);

namespace Tests\Feature\Billing;

use Illuminate\Support\Str;
use Platform\Billing\Enums\SubscriptionStatus;
use Platform\Billing\Models\Plan;
use Platform\Billing\Models\Subscription;
use Platform\Tenancy\Models\Tenant;
use Tests\Feature\PlatformTestCase;

final class TenantBillingSettingsTest extends PlatformTestCase
{
    public function test_merchant_can_read_and_update_vat_registered_setting(): void
    {
        $tenant = Tenant::query()->create([
            'slug' => 'vat-'.Str::random(6),
            'name' => 'VAT Store',
            'status' => 'active',
            'country' => 'NG',
            'settings' => ['vat_registered' => false],
        ]);

        $merchant = $this->createMerchantForTenant($tenant);
        $token = $merchant->createToken('billing-settings')->plainTextToken;
        $plan = Plan::query()->where('slug', 'starter')->firstOrFail();

        Subscription::query()->create([
            'tenant_id' => $tenant->id,
            'plan_id' => $plan->id,
            'status' => SubscriptionStatus::Active,
        ]);

        $headers = $this->merchantAuthHeaders($tenant->id, $token);

        $this->getJson('/api/v1/platform/billing/settings', $headers)
            ->assertOk()
            ->assertJsonPath('data.vat_registered', false);

        $this->putJson('/api/v1/platform/billing/settings', [
            'vat_registered' => true,
        ], $headers)
            ->assertOk()
            ->assertJsonPath('data.vat_registered', true);

        $tenant->refresh();
        $this->assertTrue((bool) ($tenant->settings['vat_registered'] ?? false));
    }
}
