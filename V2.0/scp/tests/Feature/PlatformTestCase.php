<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Platform\Identity\Enums\MerchantUserRole;
use Platform\Identity\Models\MerchantUser;
use Platform\Tenancy\Models\Tenant;
use Tests\TestCase;

abstract class PlatformTestCase extends TestCase
{
    use RefreshDatabase;

    protected function createMerchantForTenant(
        Tenant $tenant,
        string $email = 'merchant@test.com',
        string $password = 'password',
    ): MerchantUser {
        return MerchantUser::query()->create([
            'tenant_id' => $tenant->id,
            'email' => $email,
            'password' => $password,
            'role' => MerchantUserRole::Owner,
        ]);
    }

    protected function merchantAuthHeaders(string $tenantId, string $token): array
    {
        return [
            'X-Tenant-ID' => $tenantId,
            'Authorization' => 'Bearer '.$token,
        ];
    }

    protected function refreshInMemoryDatabase(): void
    {
        foreach ([
            'Platform/Tenancy/database/migrations',
            'Platform/Identity/database/migrations',
            'Platform/Billing/database/migrations',
            'Platform/Provisioning/database/migrations',
            'Modules/Commerce/Catalog/database/migrations',
            'Modules/Commerce/Cart/database/migrations',
            'Modules/Commerce/Checkout/database/migrations',
            'Modules/Commerce/Orders/database/migrations',
            'Modules/Commerce/Shipping/database/migrations',
        ] as $path) {
            $this->artisan('migrate', [
                '--path' => base_path($path),
            ]);
        }
    }
}
