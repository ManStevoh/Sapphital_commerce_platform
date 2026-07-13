<?php

declare(strict_types=1);

namespace Tests\Feature\Identity;

use Illuminate\Support\Facades\Hash;
use Platform\Identity\Models\PlatformAdmin;
use Platform\Identity\Services\TotpService;

final class PlatformAuthTest extends IdentityTestCase
{
    public function test_platform_login_requires_mfa_when_enrolled(): void
    {
        $admin = $this->createPlatformAdmin();
        $secret = $this->enrollPlatformAdminMfa($admin);

        $response = $this->postJson('/api/v1/auth/platform/login', [
            'email' => 'admin@sapphital.test',
            'password' => 'platform-secret',
        ]);

        $response->assertOk()
            ->assertJson([
                'mfa_required' => true,
                'token_type' => 'Bearer',
            ])
            ->assertJsonMissing(['token' => '']);

        $pendingToken = (string) $response->json('token');
        $code = app(TotpService::class)->currentCode($secret);

        $this->getJson('/api/v1/platform/tenants', [
            'Authorization' => 'Bearer '.$pendingToken,
        ])->assertForbidden()
            ->assertJsonPath('mfa_required', true);

        $verified = $this->postJson('/api/v1/auth/platform/mfa/verify', [
            'code' => $code,
        ], [
            'Authorization' => 'Bearer '.$pendingToken,
        ]);

        $verified->assertOk()
            ->assertJsonStructure(['token', 'token_type']);

        $fullToken = (string) $verified->json('token');

        $this->getJson('/api/v1/platform/tenants', [
            'Authorization' => 'Bearer '.$fullToken,
        ])->assertOk();
    }

    public function test_platform_login_requires_enrollment_when_mfa_not_configured(): void
    {
        $this->createPlatformAdmin();

        $response = $this->postJson('/api/v1/auth/platform/login', [
            'email' => 'admin@sapphital.test',
            'password' => 'platform-secret',
        ]);

        $response->assertOk()
            ->assertJson([
                'mfa_enrollment_required' => true,
                'token_type' => 'Bearer',
            ]);
    }

    public function test_platform_mfa_enrollment_flow_issues_full_token(): void
    {
        $this->createPlatformAdmin();

        $login = $this->postJson('/api/v1/auth/platform/login', [
            'email' => 'admin@sapphital.test',
            'password' => 'platform-secret',
        ]);

        $setupToken = (string) $login->json('token');

        $setup = $this->postJson('/api/v1/auth/platform/mfa/setup', [], [
            'Authorization' => 'Bearer '.$setupToken,
        ]);

        $setup->assertOk()
            ->assertJsonStructure(['data' => ['secret', 'otpauth_uri']]);

        $secret = (string) $setup->json('data.secret');
        $code = app(TotpService::class)->currentCode($secret);

        $confirm = $this->postJson('/api/v1/auth/platform/mfa/confirm', [
            'secret' => $secret,
            'code' => $code,
        ], [
            'Authorization' => 'Bearer '.$setupToken,
        ]);

        $confirm->assertOk()
            ->assertJsonStructure(['backup_codes', 'token', 'token_type']);

        $this->assertCount(10, $confirm->json('backup_codes'));

        $fullToken = (string) $confirm->json('token');

        $this->getJson('/api/v1/platform/tenants', [
            'Authorization' => 'Bearer '.$fullToken,
        ])->assertOk();

        $admin = PlatformAdmin::query()->where('email', 'admin@sapphital.test')->firstOrFail();
        $this->assertNotNull($admin->mfa_confirmed_at);
    }

    public function test_backup_code_can_complete_mfa_challenge(): void
    {
        $admin = $this->createPlatformAdmin();
        $plainCode = 'ABCD-EFGH';
        $admin->forceFill([
            'mfa_secret' => 'JBSWY3DPEHPK3PXP',
            'mfa_confirmed_at' => now(),
            'mfa_backup_codes' => [Hash::make($plainCode)],
        ])->save();

        $login = $this->postJson('/api/v1/auth/platform/login', [
            'email' => 'admin@sapphital.test',
            'password' => 'platform-secret',
        ]);

        $pendingToken = (string) $login->json('token');

        $verified = $this->postJson('/api/v1/auth/platform/mfa/verify', [
            'code' => $plainCode,
        ], [
            'Authorization' => 'Bearer '.$pendingToken,
        ]);

        $verified->assertOk();

        $admin->refresh();
        $this->assertSame([], $admin->mfa_backup_codes);
    }
}
