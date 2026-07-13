<?php

declare(strict_types=1);

namespace Tests\Security;

use Illuminate\Support\Str;
use Platform\Billing\Enums\SubscriptionStatus;
use Platform\Billing\Models\Plan;
use Platform\Billing\Models\Subscription;
use Platform\Identity\Models\PlatformAdmin;
use Platform\Tenancy\Models\Tenant;
use Tests\Feature\PlatformTestCase;

final class AuthzAllowedPathsTest extends PlatformTestCase
{
    public function test_public_health_and_plans_routes_succeed(): void
    {
        $this->getJson('/api/v1/platform/billing/plans')
            ->assertOk()
            ->assertJsonPath('data.0.slug', 'starter');

        $this->getJson('/api/v1/commerce/catalog/health')
            ->assertOk()
            ->assertJsonPath('package', 'catalog');
    }

    public function test_tenant_scoped_catalog_list_succeeds_with_tenant_header(): void
    {
        $tenant = $this->createTenant();

        $this->getJson('/api/v1/commerce/catalog/products', [
            'X-Tenant-ID' => $tenant->id,
        ])->assertOk();
    }

    public function test_sanctum_me_succeeds_with_merchant_token(): void
    {
        $tenant = $this->createTenant();
        $merchant = $this->createMerchantForTenant($tenant);
        $token = $merchant->createToken('allowed')->plainTextToken;

        $this->getJson('/api/v1/auth/me', [
            'Authorization' => 'Bearer '.$token,
        ])->assertOk()
            ->assertJsonPath('email', $merchant->email);
    }

    public function test_merchant_orders_index_succeeds_with_active_subscription(): void
    {
        $tenant = $this->createTenant();
        $merchant = $this->createMerchantForTenant($tenant);
        $token = $merchant->createToken('allowed')->plainTextToken;
        $plan = Plan::query()->where('slug', 'starter')->firstOrFail();

        Subscription::query()->create([
            'tenant_id' => $tenant->id,
            'plan_id' => $plan->id,
            'status' => SubscriptionStatus::Active,
        ]);

        $this->getJson('/api/v1/commerce/orders', $this->merchantAuthHeaders($tenant->id, $token))
            ->assertOk()
            ->assertJsonStructure(['data']);
    }

    public function test_platform_admin_can_list_tenants(): void
    {
        $this->createPlatformAdmin('allowed-admin@test.com', 'platform-secret-12');
        $this->enrollPlatformAdminMfa(
            PlatformAdmin::query()->where('email', 'allowed-admin@test.com')->firstOrFail(),
        );

        $token = $this->loginPlatformAdmin('allowed-admin@test.com', 'platform-secret-12');

        $this->getJson('/api/v1/platform/tenants', [
            'Authorization' => 'Bearer '.$token,
        ])->assertOk()
            ->assertJsonStructure(['data']);
    }

    private function createTenant(): Tenant
    {
        return Tenant::query()->create([
            'slug' => 'allowed-'.Str::random(6),
            'name' => 'Allowed Tenant',
            'status' => 'active',
            'country' => 'NG',
        ]);
    }
}
