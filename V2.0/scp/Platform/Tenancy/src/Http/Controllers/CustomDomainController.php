<?php

declare(strict_types=1);

namespace Platform\Tenancy\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Platform\Tenancy\Models\CustomDomain;
use Platform\Tenancy\Models\Tenant;
use Platform\Tenancy\Services\CustomDomainService;
use Symfony\Component\HttpFoundation\Response;

final class CustomDomainController
{
    public function __construct(
        private readonly CustomDomainService $domains,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $tenantId = $this->tenantId($request);

        if ($tenantId === null) {
            return $this->missingTenant();
        }

        $rows = $this->domains->listForTenant($tenantId);

        return response()->json([
            'data' => array_map(fn (CustomDomain $domain): array => $this->payload($domain), $rows),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $tenantId = $this->tenantId($request);

        if ($tenantId === null) {
            return $this->missingTenant();
        }

        $validated = $request->validate([
            'domain' => ['required', 'string', 'max:255'],
            'is_primary' => ['sometimes', 'boolean'],
        ]);

        try {
            $domain = $this->domains->attach(
                $tenantId,
                $validated['domain'],
                (bool) ($validated['is_primary'] ?? true),
            );
        } catch (ValidationException $exception) {
            return response()->json([
                'message' => collect($exception->errors())->flatten()->first() ?? 'Unable to attach domain.',
                'errors' => $exception->errors(),
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        return response()->json([
            'data' => $this->payload($domain),
        ], Response::HTTP_CREATED);
    }

    public function verify(Request $request, string $id): JsonResponse
    {
        $tenantId = $this->tenantId($request);

        if ($tenantId === null) {
            return $this->missingTenant();
        }

        try {
            $domain = $this->domains->verify($tenantId, $id);
        } catch (ValidationException $exception) {
            return response()->json([
                'message' => collect($exception->errors())->flatten()->first() ?? 'Verification failed.',
                'errors' => $exception->errors(),
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        return response()->json([
            'data' => $this->payload($domain),
        ]);
    }

    public function destroy(Request $request, string $id): JsonResponse
    {
        $tenantId = $this->tenantId($request);

        if ($tenantId === null) {
            return $this->missingTenant();
        }

        $this->domains->detach($tenantId, $id);

        return response()->json(null, Response::HTTP_NO_CONTENT);
    }

    public function showByHost(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'host' => ['required', 'string', 'max:255'],
        ]);

        $tenantId = $this->domains->resolveTenantIdByHost($validated['host']);

        if ($tenantId === null) {
            return response()->json(['message' => 'Tenant not found.'], Response::HTTP_NOT_FOUND);
        }

        $tenant = Tenant::query()->find($tenantId, ['id', 'slug', 'name', 'status']);

        if ($tenant === null) {
            return response()->json(['message' => 'Tenant not found.'], Response::HTTP_NOT_FOUND);
        }

        return response()->json([
            'id' => $tenant->id,
            'slug' => $tenant->slug,
            'name' => $tenant->name,
            'status' => $tenant->status,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function payload(CustomDomain $domain): array
    {
        return [
            'id' => $domain->id,
            'domain' => $domain->domain,
            'is_primary' => $domain->is_primary,
            'status' => $domain->status,
            'verified_at' => $domain->verified_at?->toIso8601String(),
            'dns' => $this->domains->dnsInstructions($domain),
        ];
    }

    private function tenantId(Request $request): ?string
    {
        $tenantId = $request->attributes->get('tenant_id');

        return is_string($tenantId) && $tenantId !== '' ? $tenantId : null;
    }

    private function missingTenant(): JsonResponse
    {
        return response()->json(['message' => 'Tenant context required.'], Response::HTTP_FORBIDDEN);
    }
}
