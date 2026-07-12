<?php

declare(strict_types=1);

namespace Platform\Provisioning\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Platform\Provisioning\Services\ProvisionTenantService;

final class ProvisioningStatusController
{
    public function __construct(
        private readonly ProvisionTenantService $provisionTenantService,
    ) {}

    public function show(string $tenantId): JsonResponse
    {
        $run = $this->provisionTenantService->findLatestRunForTenant($tenantId);

        if ($run === null) {
            return response()->json([
                'message' => 'Provisioning run not found.',
            ], 404);
        }

        return response()->json([
            'tenant_id' => $run->tenant_id,
            'provisioning_run_id' => $run->id,
            'status' => $run->status->value,
            'steps' => $run->steps,
            'started_at' => $run->started_at?->toIso8601String(),
            'completed_at' => $run->completed_at?->toIso8601String(),
            'error' => $run->error,
        ]);
    }
}
