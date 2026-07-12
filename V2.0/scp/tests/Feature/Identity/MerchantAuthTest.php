<?php

declare(strict_types=1);

namespace Tests\Feature\Identity;

use Platform\Identity\Enums\MerchantUserRole;
use Platform\Identity\Models\MerchantUser;
use Platform\Tenancy\Models\Tenant;

final class MerchantAuthTest extends IdentityTestCase
{
    public function test_merchant_login_succeeds_with_valid_credentials(): void
    {
        $tenant = Tenant::query()->create([
            'slug' => 'acme-store',
            'name' => 'Acme Store',
            'status' => 'active',
            'country' => 'NG',
        ]);

        MerchantUser::query()->create([
            'tenant_id' => $tenant->id,
            'email' => 'owner@acme.test',
            'password' => 'secret-password',
            'role' => MerchantUserRole::Owner,
        ]);

        $response = $this->postJson('/api/v1/auth/merchant/login', [
            'email' => 'owner@acme.test',
            'password' => 'secret-password',
        ]);

        $response->assertOk()
            ->assertJsonStructure([
                'token',
                'token_type',
            ])
            ->assertJson([
                'token_type' => 'Bearer',
            ]);

        $this->assertNotEmpty($response->json('token'));
    }

    public function test_merchant_login_fails_with_invalid_credentials(): void
    {
        $tenant = Tenant::query()->create([
            'slug' => 'acme-store',
            'name' => 'Acme Store',
            'status' => 'active',
            'country' => 'NG',
        ]);

        MerchantUser::query()->create([
            'tenant_id' => $tenant->id,
            'email' => 'owner@acme.test',
            'password' => 'secret-password',
            'role' => MerchantUserRole::Owner,
        ]);

        $wrongPasswordResponse = $this->postJson('/api/v1/auth/merchant/login', [
            'email' => 'owner@acme.test',
            'password' => 'wrong-password',
        ]);

        $wrongPasswordResponse->assertUnauthorized()
            ->assertJson([
                'message' => 'Invalid credentials.',
            ]);

        $unknownEmailResponse = $this->postJson('/api/v1/auth/merchant/login', [
            'email' => 'unknown@acme.test',
            'password' => 'secret-password',
        ]);

        $unknownEmailResponse->assertUnauthorized()
            ->assertJson([
                'message' => 'Invalid credentials.',
            ]);
    }
}
