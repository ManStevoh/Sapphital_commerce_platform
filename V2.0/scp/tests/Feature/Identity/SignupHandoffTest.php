<?php

declare(strict_types=1);

namespace Tests\Feature\Identity;

use Platform\Identity\Services\SignupHandoffService;
use Platform\Tenancy\Models\Tenant;
use Tests\Feature\PlatformTestCase;

final class SignupHandoffTest extends PlatformTestCase
{
    public function test_signup_returns_handoff_token_and_exchange_grants_session(): void
    {
        $signup = $this->postJson('/api/v1/signup', [
            'email' => 'handoff@example.com',
            'password' => 'securepassword12',
            'store_name' => 'Handoff Shop',
            'plan_slug' => 'starter',
        ]);

        $signup->assertAccepted()
            ->assertJsonStructure([
                'tenant_id',
                'admin_handoff_token',
                'email',
            ])
            ->assertJsonPath('email', 'handoff@example.com');

        $tenantId = (string) $signup->json('tenant_id');
        $handoffToken = (string) $signup->json('admin_handoff_token');

        $exchange = $this->postJson('/api/v1/auth/merchant/handoff', [
            'handoff_token' => $handoffToken,
        ]);

        $exchange->assertOk()
            ->assertJsonPath('tenant_id', $tenantId)
            ->assertJsonStructure(['token', 'token_type']);

        $this->postJson('/api/v1/auth/merchant/handoff', [
            'handoff_token' => $handoffToken,
        ])->assertUnprocessable();
    }

    public function test_handoff_rejects_unknown_token(): void
    {
        $this->postJson('/api/v1/auth/merchant/handoff', [
            'handoff_token' => 'invalid-token',
        ])->assertUnprocessable();
    }

    public function test_handoff_service_token_is_single_use(): void
    {
        $tenant = Tenant::query()->create([
            'slug' => 'handoff-expire',
            'name' => 'Handoff Expire',
            'status' => 'active',
            'country' => 'NG',
        ]);

        $merchant = $this->createMerchantForTenant($tenant, 'expire@example.com', 'password12345');
        $service = app(SignupHandoffService::class);
        $token = $service->create((string) $merchant->id, $tenant->id);

        $service->consume($token);

        $this->expectException(\Illuminate\Validation\ValidationException::class);
        $service->consume($token);
    }
}
