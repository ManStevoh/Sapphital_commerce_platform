<?php

declare(strict_types=1);

namespace Platform\Billing\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Platform\Billing\Services\TenantBillingSettingsService;
use Symfony\Component\HttpFoundation\Response;

final class TenantBillingSettingsController
{
    public function __construct(
        private readonly TenantBillingSettingsService $settings,
    ) {}

    public function show(Request $request): JsonResponse
    {
        $tenantId = $this->tenantId($request);

        if ($tenantId === null) {
            return $this->missingTenantResponse();
        }

        return response()->json([
            'data' => $this->settings->getForTenant($tenantId),
        ]);
    }

    public function update(Request $request): JsonResponse
    {
        $tenantId = $this->tenantId($request);

        if ($tenantId === null) {
            return $this->missingTenantResponse();
        }

        $validated = $request->validate([
            'vat_registered' => ['required', 'boolean'],
        ]);

        $this->settings->updateVatRegistered($tenantId, (bool) $validated['vat_registered']);

        return response()->json([
            'data' => $this->settings->getForTenant($tenantId),
        ]);
    }

    private function tenantId(Request $request): ?string
    {
        $tenantId = $request->attributes->get('tenant_id');

        return is_string($tenantId) && $tenantId !== '' ? $tenantId : null;
    }

    private function missingTenantResponse(): JsonResponse
    {
        return response()->json([
            'message' => 'Tenant context required.',
        ], Response::HTTP_FORBIDDEN);
    }
}
