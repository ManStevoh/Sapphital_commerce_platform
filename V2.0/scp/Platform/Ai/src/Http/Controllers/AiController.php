<?php

declare(strict_types=1);

namespace Platform\Ai\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Platform\Ai\Models\AiUsageEvent;
use Platform\Ai\Services\ProductDescriptionGenerator;
use Platform\Ai\Services\SeoMetaGenerator;
use Platform\Tenancy\Models\Tenant;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;

final class AiController
{
    public function __construct(
        private readonly ProductDescriptionGenerator $descriptions,
        private readonly SeoMetaGenerator $seo,
    ) {}

    public function generateProductDescription(Request $request): JsonResponse
    {
        $tenantId = $this->tenantId($request);

        if ($tenantId === null) {
            return response()->json(['message' => 'Tenant context required.'], Response::HTTP_FORBIDDEN);
        }

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'keywords' => ['required', 'array', 'min:1', 'max:3'],
            'keywords.*' => ['string', 'max:64'],
        ]);

        try {
            $result = $this->descriptions->generate(
                $tenantId,
                $validated['title'],
                $validated['keywords'],
            );
        } catch (HttpException $exception) {
            return response()->json([
                'message' => $exception->getMessage(),
            ], $exception->getStatusCode());
        }

        return response()->json([
            'data' => [
                'draft' => $result->text,
                'provider' => $result->provider,
                'model' => $result->model,
                'degraded' => $result->degraded,
                'requires_merchant_edit' => true,
                'watermark' => 'ai-generated-draft',
            ],
        ]);
    }

    public function generateSeoMeta(Request $request): JsonResponse
    {
        $tenantId = $this->tenantId($request);

        if ($tenantId === null) {
            return response()->json(['message' => 'Tenant context required.'], Response::HTTP_FORBIDDEN);
        }

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'content' => ['required', 'string', 'max:2000'],
        ]);

        try {
            $result = $this->seo->generate(
                $tenantId,
                $validated['title'],
                $validated['content'],
            );
        } catch (HttpException $exception) {
            return response()->json([
                'message' => $exception->getMessage(),
            ], $exception->getStatusCode());
        }

        return response()->json([
            'data' => [
                'draft' => $result->text,
                'provider' => $result->provider,
                'model' => $result->model,
                'degraded' => $result->degraded,
                'requires_merchant_edit' => true,
                'watermark' => 'ai-generated-draft',
            ],
        ]);
    }

    public function usage(Request $request): JsonResponse
    {
        $tenantId = $this->tenantId($request);

        if ($tenantId === null) {
            return response()->json(['message' => 'Tenant context required.'], Response::HTTP_FORBIDDEN);
        }

        $monthStart = now()->startOfMonth();

        $events = AiUsageEvent::query()
            ->where('tenant_id', $tenantId)
            ->where('occurred_at', '>=', $monthStart)
            ->get(['total_tokens', 'feature_key']);

        $byFeature = $events->groupBy('feature_key')->map(
            static fn ($group): array => [
                'requests' => $group->count(),
                'tokens' => (int) $group->sum('total_tokens'),
            ],
        );

        return response()->json([
            'data' => [
                'period_start' => $monthStart->toIso8601String(),
                'requests' => $events->count(),
                'tokens' => (int) $events->sum('total_tokens'),
                'by_feature' => $byFeature,
            ],
        ]);
    }

    public function updateSettings(Request $request): JsonResponse
    {
        $tenantId = $this->tenantId($request);

        if ($tenantId === null) {
            return response()->json(['message' => 'Tenant context required.'], Response::HTTP_FORBIDDEN);
        }

        $validated = $request->validate([
            'ai_enabled' => ['required', 'boolean'],
        ]);

        $tenant = Tenant::query()->findOrFail($tenantId);
        $settings = is_array($tenant->settings) ? $tenant->settings : [];
        $settings['ai_enabled'] = (bool) $validated['ai_enabled'];
        $tenant->update(['settings' => $settings]);

        return response()->json([
            'data' => [
                'ai_enabled' => (bool) $settings['ai_enabled'],
            ],
        ]);
    }

    private function tenantId(Request $request): ?string
    {
        $tenantId = $request->attributes->get('tenant_id');

        return is_string($tenantId) && $tenantId !== '' ? $tenantId : null;
    }
}
