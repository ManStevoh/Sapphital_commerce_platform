<?php

declare(strict_types=1);

namespace Tests\Feature\Identity;

use Platform\Identity\Enums\MerchantUserRole;
use Platform\Identity\Models\MerchantUser;
use Platform\Tenancy\Models\Tenant;

final class MeEndpointTest extends IdentityTestCase
{
    public function test_me_requires_authentication(): void
    {
        $response = $this->getJson('/api/v1/auth/me');

        $response->assertUnauthorized();
    }

    public function test_me_returns_authenticated_user(): void
    {
        $tenant = Tenant::query()->create([
            'slug' => 'acme-store',
            'name' => 'Acme Store',
            'status' => 'active',
            'country' => 'NG',
        ]);

        $user = MerchantUser::query()->create([
            'tenant_id' => $tenant->id,
            'email' => 'owner@acme.test',
            'password' => 'secret-password',
            'role' => MerchantUserRole::Owner,
        ]);

        $loginResponse = $this->postJson('/api/v1/auth/merchant/login', [
            'email' => 'owner@acme.test',
            'password' => 'secret-password',
        ]);

        $token = $loginResponse->json('token');

        $response = $this->getJson('/api/v1/auth/me', [
            'Authorization' => 'Bearer '.$token,
        ]);

        $response->assertOk()
            ->assertJson([
                'id' => $user->id,
                'type' => 'merchant',
                'email' => 'owner@acme.test',
                'tenant_id' => $tenant->id,
            ]);
    }
}
