<?php

declare(strict_types=1);

namespace Modules\Commerce\Catalog\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use InvalidArgumentException;
use Modules\Commerce\Catalog\Services\ThemeResolver;
use Platform\Tenancy\Models\Tenant;
use Symfony\Component\HttpFoundation\Response;

final class StorefrontController
{
    public function __construct(
        private readonly ThemeResolver $themeResolver,
    ) {}

    public function themes(): JsonResponse
    {
        return response()->json([
            'data' => $this->themeResolver->listAvailableThemes(),
        ]);
    }

    public function theme(Request $request): JsonResponse
    {
        $tenantId = $this->tenantId($request);

        if ($tenantId === null) {
            return $this->missingTenantResponse();
        }

        $tenant = Tenant::query()->find($tenantId);

        if ($tenant === null) {
            return response()->json([
                'message' => 'Tenant not found.',
            ], Response::HTTP_NOT_FOUND);
        }

        try {
            $config = $this->themeResolver->resolveForTenant($tenant);
        } catch (InvalidArgumentException $exception) {
            return response()->json([
                'message' => $exception->getMessage(),
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        return response()->json([
            'data' => $config,
        ]);
    }

    public function preview(Request $request, string $themeId): JsonResponse
    {
        $tenantId = $this->tenantId($request);

        if ($tenantId === null) {
            return $this->missingTenantResponse();
        }

        if (! $this->themeResolver->themeExists($themeId)) {
            return response()->json([
                'message' => "Theme package not found: {$themeId}",
            ], Response::HTTP_NOT_FOUND);
        }

        $tenant = Tenant::query()->find($tenantId);

        if ($tenant === null) {
            return response()->json([
                'message' => 'Tenant not found.',
            ], Response::HTTP_NOT_FOUND);
        }

        $settings = is_array($tenant->settings) ? $tenant->settings : [];
        $previewTenant = $tenant->replicate();
        $previewTenant->settings = array_merge($settings, ['theme_id' => $themeId]);

        try {
            $config = $this->themeResolver->resolveForTenant($previewTenant);
        } catch (InvalidArgumentException $exception) {
            return response()->json([
                'message' => $exception->getMessage(),
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        return response()->json([
            'data' => $config,
            'meta' => [
                'preview' => true,
                'active_theme_id' => is_string($settings['theme_id'] ?? null)
                    ? $settings['theme_id']
                    : ThemeResolver::DEFAULT_THEME_ID,
            ],
        ]);
    }

    public function applyTheme(Request $request): JsonResponse
    {
        $tenantId = $this->tenantId($request);

        if ($tenantId === null) {
            return $this->missingTenantResponse();
        }

        $tenant = Tenant::query()->find($tenantId);

        if ($tenant === null) {
            return response()->json([
                'message' => 'Tenant not found.',
            ], Response::HTTP_NOT_FOUND);
        }

        $validated = $request->validate([
            'theme_id' => ['required', 'string', 'max:64'],
        ]);

        try {
            $result = $this->themeResolver->applyTheme($tenant, $validated['theme_id']);
        } catch (InvalidArgumentException $exception) {
            return response()->json([
                'message' => $exception->getMessage(),
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        return response()->json([
            'data' => $result['theme'],
            'portability' => $result['portability'],
        ]);
    }

    public function updateThemeSettings(Request $request): JsonResponse
    {
        $tenantId = $this->tenantId($request);

        if ($tenantId === null) {
            return $this->missingTenantResponse();
        }

        $tenant = Tenant::query()->find($tenantId);

        if ($tenant === null) {
            return response()->json([
                'message' => 'Tenant not found.',
            ], Response::HTTP_NOT_FOUND);
        }

        $validated = $request->validate([
            'primary_color' => ['sometimes', 'string', 'max:32'],
            'font_heading' => ['sometimes', 'string', 'max:120'],
            'logo_url' => ['sometimes', 'nullable', 'string', 'max:2048'],
        ]);

        $settings = is_array($tenant->settings) ? $tenant->settings : [];
        $themeSettings = is_array($settings['theme_settings'] ?? null) ? $settings['theme_settings'] : [];
        $settings['theme_settings'] = array_merge($themeSettings, $validated);

        $tenant->update([
            'settings' => $settings,
        ]);

        try {
            $config = $this->themeResolver->resolveForTenant($tenant->fresh());
        } catch (InvalidArgumentException $exception) {
            return response()->json([
                'message' => $exception->getMessage(),
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        return response()->json([
            'data' => $config,
        ]);
    }

    private function tenantId(Request $request): ?string
    {
        $tenantId = $request->attributes->get('tenant_id');

        if (! is_string($tenantId) || $tenantId === '') {
            return null;
        }

        return $tenantId;
    }

    private function missingTenantResponse(): JsonResponse
    {
        return response()->json([
            'message' => 'Tenant context required.',
        ], Response::HTTP_FORBIDDEN);
    }
}
