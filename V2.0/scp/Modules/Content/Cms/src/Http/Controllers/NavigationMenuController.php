<?php

declare(strict_types=1);

namespace Modules\Content\Cms\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Content\Cms\Enums\NavigationLocation;
use Modules\Content\Cms\Models\NavigationMenu;
use Symfony\Component\HttpFoundation\Response;

final class NavigationMenuController
{
    public function show(Request $request, string $location): JsonResponse
    {
        $tenantId = $this->tenantId($request);

        if ($tenantId === null) {
            return $this->missingTenantResponse();
        }

        if (! in_array($location, [NavigationLocation::Header->value, NavigationLocation::Footer->value], true)) {
            return response()->json(['message' => 'Invalid menu location.'], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $menu = NavigationMenu::query()
            ->where('tenant_id', $tenantId)
            ->where('location', $location)
            ->first();

        return response()->json([
            'data' => $menu ?? [
                'location' => $location,
                'links' => [],
            ],
        ]);
    }

    public function upsert(Request $request, string $location): JsonResponse
    {
        $tenantId = $this->tenantId($request);

        if ($tenantId === null) {
            return $this->missingTenantResponse();
        }

        $validated = $request->validate([
            'links' => ['required', 'array'],
            'links.*.label' => ['required', 'string', 'max:128'],
            'links.*.href' => ['required', 'string', 'max:2048'],
            'links.*.open_in_new_tab' => ['sometimes', 'boolean'],
        ]);

        if (! in_array($location, [NavigationLocation::Header->value, NavigationLocation::Footer->value], true)) {
            return response()->json(['message' => 'Invalid menu location.'], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $menu = NavigationMenu::query()->updateOrCreate(
            [
                'tenant_id' => $tenantId,
                'location' => $location,
            ],
            [
                'links' => $validated['links'],
            ],
        );

        return response()->json(['data' => $menu]);
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
