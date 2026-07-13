<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Platform\Identity\Enums\MerchantUserRole;
use Platform\Identity\Models\MerchantUser;
use Platform\Identity\Models\PlatformAdmin;
use Platform\Identity\Services\TotpService;
use Platform\Tenancy\Models\Tenant;
use Platform\Tenancy\Support\TenantContext;
use Tests\TestCase;

abstract class PlatformTestCase extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        TenantContext::clear();

        parent::tearDown();
    }

    protected function createMerchantForTenant(
        Tenant $tenant,
        string $email = 'merchant@test.com',
        string $password = 'password',
        MerchantUserRole $role = MerchantUserRole::Owner,
    ): MerchantUser {
        return MerchantUser::query()->create([
            'tenant_id' => $tenant->id,
            'email' => $email,
            'password' => $password,
            'role' => $role,
        ]);
    }

    protected function merchantAuthHeaders(string $tenantId, string $token): array
    {
        return [
            'X-Tenant-ID' => $tenantId,
            'Authorization' => 'Bearer '.$token,
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function idempotencyHeaders(?string $key = null): array
    {
        return [
            'Idempotency-Key' => $key ?? (string) Str::uuid(),
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function tenantMoneyHeaders(string $tenantId, ?string $idempotencyKey = null): array
    {
        return array_merge(
            ['X-Tenant-ID' => $tenantId],
            $this->idempotencyHeaders($idempotencyKey),
        );
    }

    protected function createPlatformAdmin(
        string $email = 'admin@sapphital.test',
        string $password = 'platform-secret',
    ): PlatformAdmin {
        return PlatformAdmin::query()->create([
            'email' => $email,
            'password' => $password,
        ]);
    }

    protected function enrollPlatformAdminMfa(
        PlatformAdmin $admin,
        string $secret = 'JBSWY3DPEHPK3PXP',
    ): string {
        $admin->forceFill([
            'mfa_secret' => $secret,
            'mfa_confirmed_at' => now(),
            'mfa_backup_codes' => [],
        ])->save();

        return $secret;
    }

    protected function loginPlatformAdmin(
        string $email,
        string $password,
        ?string $mfaSecret = null,
    ): string {
        $login = $this->postJson('/api/v1/auth/platform/login', [
            'email' => $email,
            'password' => $password,
        ]);

        $login->assertOk();

        if ($login->json('mfa_required') === true) {
            $pendingToken = (string) $login->json('token');
            $secret = $mfaSecret ?? 'JBSWY3DPEHPK3PXP';
            $code = app(TotpService::class)->currentCode($secret);

            $verify = $this->postJson('/api/v1/auth/platform/mfa/verify', [
                'code' => $code,
            ], [
                'Authorization' => 'Bearer '.$pendingToken,
            ]);

            $verify->assertOk();

            return (string) $verify->json('token');
        }

        if ($login->json('mfa_enrollment_required') === true) {
            $this->fail('Platform admin MFA enrollment required — enroll before login.');
        }

        return (string) $login->json('token');
    }

    protected function refreshInMemoryDatabase(): void
    {
        foreach ([
            'Platform/Tenancy/database/migrations',
            'Platform/Identity/database/migrations',
            'Platform/Billing/database/migrations',
            'Platform/Provisioning/database/migrations',
            'Platform/FinancialServices/database/migrations',
            'Modules/Commerce/Catalog/database/migrations',
            'Modules/Commerce/Cart/database/migrations',
            'Modules/Commerce/Checkout/database/migrations',
            'Modules/Commerce/Orders/database/migrations',
            'Modules/Commerce/Shipping/database/migrations',
            'Modules/Content/Cms/database/migrations',
            'Platform/Ai/database/migrations',
        ] as $path) {
            $this->artisan('migrate', [
                '--path' => base_path($path),
            ]);
        }
    }
}
