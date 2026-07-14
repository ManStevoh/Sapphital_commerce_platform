<?php

declare(strict_types=1);

namespace Tests\Feature\Identity;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Platform\Identity\Enums\MerchantUserRole;
use Platform\Identity\Models\MerchantUser;
use Platform\Identity\Services\TotpService;
use Platform\Tenancy\Models\Tenant;

final class MerchantMfaTest extends IdentityTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config(['identity.merchant_mfa_enforced' => true]);
        Auth::forgetGuards();
    }

    public function test_owner_login_requires_enrollment_when_mfa_not_configured(): void
    {
        $this->createOwner();

        $response = $this->postJson('/api/v1/auth/merchant/login', [
            'email' => 'owner@acme.test',
            'password' => 'secret-password',
        ]);

        $response->assertOk()
            ->assertJson([
                'mfa_enrollment_required' => true,
                'token_type' => 'Bearer',
            ])
            ->assertJsonStructure(['token', 'tenant_id']);
    }

    public function test_owner_login_requires_mfa_when_enrolled(): void
    {
        $user = $this->createOwner();
        $secret = $this->enrollMerchantMfa($user);

        $response = $this->postJson('/api/v1/auth/merchant/login', [
            'email' => 'owner@acme.test',
            'password' => 'secret-password',
        ]);

        $response->assertOk()
            ->assertJson([
                'mfa_required' => true,
                'token_type' => 'Bearer',
            ]);

        $pendingToken = (string) $response->json('token');
        $code = app(TotpService::class)->currentCode($secret);

        $this->getJson('/api/v1/auth/merchant/sessions', [
            'Authorization' => 'Bearer '.$pendingToken,
        ])->assertForbidden()
            ->assertJsonPath('mfa_required', true);

        $verified = $this->postJson('/api/v1/auth/merchant/mfa/verify', [
            'code' => $code,
        ], [
            'Authorization' => 'Bearer '.$pendingToken,
        ]);

        $verified->assertOk()
            ->assertJsonStructure(['token', 'token_type', 'tenant_id']);

        $fullToken = (string) $verified->json('token');

        Auth::forgetGuards();

        $this->getJson('/api/v1/auth/merchant/sessions', [
            'Authorization' => 'Bearer '.$fullToken,
        ])->assertOk();
    }

    public function test_owner_mfa_enrollment_flow_issues_full_token(): void
    {
        $this->createOwner();

        $login = $this->postJson('/api/v1/auth/merchant/login', [
            'email' => 'owner@acme.test',
            'password' => 'secret-password',
        ]);

        $setupToken = (string) $login->json('token');
        $tenantId = (string) $login->json('tenant_id');

        $setup = $this->postJson('/api/v1/auth/merchant/mfa/setup', [], [
            'Authorization' => 'Bearer '.$setupToken,
        ]);

        $setup->assertOk()
            ->assertJsonStructure(['data' => ['secret', 'otpauth_uri']]);

        $secret = (string) $setup->json('data.secret');
        $code = app(TotpService::class)->currentCode($secret);

        Log::spy();

        $confirm = $this->postJson('/api/v1/auth/merchant/mfa/confirm', [
            'secret' => $secret,
            'code' => $code,
        ], [
            'Authorization' => 'Bearer '.$setupToken,
        ]);

        $confirm->assertOk()
            ->assertJsonStructure(['backup_codes', 'token', 'token_type', 'tenant_id']);

        $this->assertCount(10, $confirm->json('backup_codes'));

        Log::shouldHaveReceived('info')
            ->once()
            ->with('merchant.login.notification', \Mockery::subset([
                'email' => 'owner@acme.test',
            ]));

        $fullToken = (string) $confirm->json('token');

        $this->getJson('/api/v1/auth/merchant/sessions', [
            'Authorization' => 'Bearer '.$fullToken,
        ])->assertOk();

        $owner = MerchantUser::query()->where('email', 'owner@acme.test')->firstOrFail();
        $this->assertNotNull($owner->mfa_confirmed_at);
    }

    public function test_staff_login_skips_mfa(): void
    {
        $tenant = Tenant::query()->create([
            'slug' => 'acme-store',
            'name' => 'Acme Store',
            'status' => 'active',
            'country' => 'NG',
        ]);

        MerchantUser::query()->create([
            'tenant_id' => $tenant->id,
            'email' => 'staff@acme.test',
            'password' => 'secret-password',
            'role' => MerchantUserRole::Staff,
        ]);

        $response = $this->postJson('/api/v1/auth/merchant/login', [
            'email' => 'staff@acme.test',
            'password' => 'secret-password',
        ]);

        $response->assertOk()
            ->assertJsonMissing([
                'mfa_required' => true,
                'mfa_enrollment_required' => true,
            ])
            ->assertJsonStructure(['token', 'token_type', 'tenant_id']);

        $token = (string) $response->json('token');

        $this->getJson('/api/v1/auth/merchant/sessions', [
            'Authorization' => 'Bearer '.$token,
        ])->assertOk();
    }

    public function test_backup_code_can_complete_mfa_challenge(): void
    {
        $user = $this->createOwner();
        $plainCode = 'ABCD-EFGH';
        $user->forceFill([
            'mfa_secret' => 'JBSWY3DPEHPK3PXP',
            'mfa_confirmed_at' => now(),
            'mfa_backup_codes' => [Hash::make($plainCode)],
        ])->save();

        $login = $this->postJson('/api/v1/auth/merchant/login', [
            'email' => 'owner@acme.test',
            'password' => 'secret-password',
        ]);

        $pendingToken = (string) $login->json('token');

        $verified = $this->postJson('/api/v1/auth/merchant/mfa/verify', [
            'code' => $plainCode,
        ], [
            'Authorization' => 'Bearer '.$pendingToken,
        ]);

        $verified->assertOk();

        $user->refresh();
        $this->assertSame([], $user->mfa_backup_codes);
    }

    public function test_sessions_can_be_listed_created_and_revoked(): void
    {
        $user = $this->createOwner();
        $this->enrollMerchantMfa($user);

        $login = $this->postJson('/api/v1/auth/merchant/login', [
            'email' => 'owner@acme.test',
            'password' => 'secret-password',
        ]);

        $pending = (string) $login->json('token');
        $code = app(TotpService::class)->currentCode('JBSWY3DPEHPK3PXP');

        $verified = $this->postJson('/api/v1/auth/merchant/mfa/verify', [
            'code' => $code,
        ], [
            'Authorization' => 'Bearer '.$pending,
        ]);

        $token = (string) $verified->json('token');

        $list = $this->getJson('/api/v1/auth/merchant/sessions', [
            'Authorization' => 'Bearer '.$token,
        ]);

        $list->assertOk()
            ->assertJsonStructure(['data' => [['id', 'name', 'abilities', 'is_current']]]);

        $created = $this->postJson('/api/v1/auth/merchant/sessions', [
            'name' => 'ci-integration',
        ], [
            'Authorization' => 'Bearer '.$token,
        ]);

        $created->assertCreated()
            ->assertJsonStructure(['data' => ['id', 'name', 'token', 'token_type']]);

        $sessionId = (string) $created->json('data.id');

        $this->deleteJson('/api/v1/auth/merchant/sessions/'.$sessionId, [], [
            'Authorization' => 'Bearer '.$token,
        ])->assertOk();

        $this->assertDatabaseMissing('personal_access_tokens', [
            'id' => $sessionId,
        ]);
    }

    private function createOwner(): MerchantUser
    {
        $tenant = Tenant::query()->create([
            'slug' => 'acme-store',
            'name' => 'Acme Store',
            'status' => 'active',
            'country' => 'NG',
        ]);

        return MerchantUser::query()->create([
            'tenant_id' => $tenant->id,
            'email' => 'owner@acme.test',
            'password' => 'secret-password',
            'role' => MerchantUserRole::Owner,
        ]);
    }

    private function enrollMerchantMfa(
        MerchantUser $user,
        string $secret = 'JBSWY3DPEHPK3PXP',
    ): string {
        $user->forceFill([
            'mfa_secret' => $secret,
            'mfa_confirmed_at' => now(),
            'mfa_backup_codes' => [],
        ])->save();

        return $secret;
    }
}
