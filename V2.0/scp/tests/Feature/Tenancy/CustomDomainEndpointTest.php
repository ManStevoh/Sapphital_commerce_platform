<?php

declare(strict_types=1);

namespace Tests\Feature\Tenancy;

use Illuminate\Support\Str;
use Platform\Billing\Enums\SubscriptionStatus;
use Platform\Billing\Models\Plan;
use Platform\Billing\Models\Subscription;
use Platform\Tenancy\Models\CustomDomain;
use Platform\Tenancy\Models\Tenant;
use Platform\Tenancy\Services\CustomDomainService;
use Tests\Feature\PlatformTestCase;

final class CustomDomainEndpointTest extends PlatformTestCase
{
    public function test_growth_merchant_can_attach_verify_and_resolve_by_host(): void
    {
        $tenant = $this->createTenant();
        $headers = $this->merchantHeaders($tenant, 'growth');

        $domainName = 'www.brand-'.Str::lower(Str::random(6)).'.ng';

        $create = $this->postJson('/api/v1/platform/tenancy/domains', [
            'domain' => $domainName,
            'is_primary' => true,
        ], $headers);

        $create->assertCreated()
            ->assertJsonPath('data.domain', $domainName)
            ->assertJsonPath('data.status', 'pending')
            ->assertJsonStructure(['data' => ['dns' => ['txt_host', 'txt_value', 'cname_target']]]);

        $domainId = (string) $create->json('data.id');
        $token = (string) $create->json('data.dns.txt_value');

        config([
            'domains.fake_dns' => [
                $domainName => [
                    'txt' => $token,
                    'cname' => config('domains.cname_target'),
                ],
            ],
        ]);

        $this->postJson("/api/v1/platform/tenancy/domains/{$domainId}/verify", [], $headers)
            ->assertOk()
            ->assertJsonPath('data.status', 'active');

        $this->getJson('/api/v1/platform/tenancy/tenants/by-host?host='.urlencode($domainName))
            ->assertOk()
            ->assertJsonPath('id', $tenant->id)
            ->assertJsonPath('slug', $tenant->slug);

        $this->assertDatabaseHas('custom_domains', [
            'id' => $domainId,
            'status' => CustomDomainService::STATUS_ACTIVE,
        ]);
    }

    public function test_starter_plan_cannot_attach_custom_domain(): void
    {
        $tenant = $this->createTenant();
        $headers = $this->merchantHeaders($tenant, 'starter');

        $this->postJson('/api/v1/platform/tenancy/domains', [
            'domain' => 'shop.blocked.ng',
        ], $headers)->assertUnprocessable();
    }

    public function test_verify_fails_without_dns_records(): void
    {
        $tenant = $this->createTenant();
        $headers = $this->merchantHeaders($tenant, 'growth');

        $create = $this->postJson('/api/v1/platform/tenancy/domains', [
            'domain' => 'missing-dns.example.ng',
        ], $headers)->assertCreated();

        $domainId = (string) $create->json('data.id');

        config(['domains.fake_dns' => []]);

        $this->postJson("/api/v1/platform/tenancy/domains/{$domainId}/verify", [], $headers)
            ->assertUnprocessable();

        $this->assertSame(
            CustomDomainService::STATUS_FAILED,
            CustomDomain::query()->findOrFail($domainId)->status,
        );
    }

    /**
     * @return array<string, string>
     */
    private function merchantHeaders(Tenant $tenant, string $planSlug): array
    {
        $this->createSubscription($tenant->id, $planSlug);
        $merchant = $this->createMerchantForTenant($tenant, 'dom-'.Str::random(4).'@test.com');
        $token = $merchant->createToken('domains')->plainTextToken;

        return $this->merchantAuthHeaders($tenant->id, $token);
    }

    private function createTenant(): Tenant
    {
        return Tenant::query()->create([
            'slug' => 'dom-'.Str::random(8),
            'name' => 'Domain Tenant',
            'status' => 'active',
            'country' => 'NG',
        ]);
    }

    private function createSubscription(string $tenantId, string $planSlug): void
    {
        $plan = Plan::query()->where('slug', $planSlug)->firstOrFail();

        Subscription::query()->create([
            'tenant_id' => $tenantId,
            'plan_id' => $plan->id,
            'status' => SubscriptionStatus::Active,
        ]);
    }
}
