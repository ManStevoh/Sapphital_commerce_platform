<?php

declare(strict_types=1);

namespace Platform\Provisioning\Services;

use Platform\Provisioning\Enums\ProvisioningRunStatus;
use Platform\Provisioning\Jobs\ProvisionTenantJob;
use Platform\Provisioning\Models\ProvisioningRun;
use Platform\Tenancy\Models\Tenant;

final class ProvisionTenantService
{
    public function start(Tenant $tenant): ProvisioningRun
    {
        $tenant->update(['status' => 'provisioning']);

        $run = ProvisioningRun::query()->create([
            'tenant_id' => $tenant->id,
            'status' => ProvisioningRunStatus::Pending,
            'steps' => ProvisionTenantJob::initialSteps(),
        ]);

        ProvisionTenantJob::dispatch($run->id);

        return $run;
    }

    public function findLatestRunForTenant(string $tenantId): ?ProvisioningRun
    {
        return ProvisioningRun::query()
            ->where('tenant_id', $tenantId)
            ->latest()
            ->first();
    }
}
