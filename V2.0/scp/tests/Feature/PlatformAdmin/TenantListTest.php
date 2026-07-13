<?php

declare(strict_types=1);

namespace Tests\Feature\PlatformAdmin;

use Platform\Identity\Enums\MerchantUserRole;
use Platform\Identity\Models\MerchantUser;
use Platform\Tenancy\Models\Tenant;
use Tests\Feature\PlatformTestCase;

final class TenantListTest extends PlatformTestCase
{
    public function test_platform_admin_can_list_tenants(): void
    {
        $admin = $this->createPlatformAdmin();
        $this->enrollPlatformAdminMfa($admin);

        Tenant::query()->create([
            'slug' => 'lagos-tech',
            'name' => 'Lagos Tech Shop',
            'status' => 'active',
            'country' => 'NG',
        ]);

        Tenant::query()->create([
            'slug' => 'abuja-fashion',
            'name' => 'Abuja Fashion',
            'status' => 'trial',
            'country' => 'NG',
        ]);

        $token = $this->loginPlatformAdmin('admin@sapphital.test', 'platform-secret');

        $response = $this->getJson('/api/v1/platform/tenants', [
            'Authorization' => 'Bearer '.$token,
        ]);

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'slug', 'name', 'status', 'country', 'created_at'],
                ],
                'meta' => ['total'],
            ])
            ->assertJsonPath('meta.total', 2);

        $this->assertCount(2, $response->json('data'));
    }

    public function test_merchant_token_cannot_list_tenants(): void
    {
        $tenant = Tenant::query()->create([
            'slug' => 'merchant-store',
            'name' => 'Merchant Store',
            'status' => 'active',
            'country' => 'NG',
        ]);

        MerchantUser::query()->create([
            'tenant_id' => $tenant->id,
            'email' => 'owner@merchant.test',
            'password' => 'secret-password',
            'role' => MerchantUserRole::Owner,
        ]);

        $loginResponse = $this->postJson('/api/v1/auth/merchant/login', [
            'email' => 'owner@merchant.test',
            'password' => 'secret-password',
        ]);

        $token = $loginResponse->json('token');

        $response = $this->getJson('/api/v1/platform/tenants', [
            'Authorization' => 'Bearer '.$token,
        ]);

        $response->assertForbidden()
            ->assertJson([
                'message' => 'Forbidden.',
            ]);
    }

    public function test_platform_admin_can_suspend_and_activate_tenant(): void
    {
        $admin = $this->createPlatformAdmin('ops@sapphital.test', 'platform-secret');
        $this->enrollPlatformAdminMfa($admin);

        $tenant = Tenant::query()->create([
            'slug' => 'suspend-me',
            'name' => 'Suspend Me Shop',
            'status' => 'active',
            'country' => 'NG',
        ]);

        $token = $this->loginPlatformAdmin('ops@sapphital.test', 'platform-secret');

        $suspend = $this->patchJson(
            '/api/v1/platform/tenants/'.$tenant->id.'/status',
            ['status' => 'suspended'],
            ['Authorization' => 'Bearer '.$token],
        );

        $suspend->assertOk()
            ->assertJsonPath('data.status', 'suspended');

        $activate = $this->patchJson(
            '/api/v1/platform/tenants/'.$tenant->id.'/status',
            ['status' => 'active'],
            ['Authorization' => 'Bearer '.$token],
        );

        $activate->assertOk()
            ->assertJsonPath('data.status', 'active');
    }
}
