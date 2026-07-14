<?php

declare(strict_types=1);

namespace Platform\Ai\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Platform\Ai\Models\AiUsageEvent;
use Platform\Ai\Services\CollectionDescriptionGenerator;
use Platform\Ai\Services\ProductDescriptionGenerator;
use Platform\Ai\Services\SeoMetaGenerator;
use Platform\Ai\Services\SupportReplyGenerator;
use Platform\Ai\Services\ZeroResultSuggestGenerator;
use Platform\Tenancy\Models\Tenant;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;

final class AiController
{
    public function __construct(
        private readonly ProductDescriptionGenerator $descriptions,
        private readonly SeoMetaGenerator $seo,
        private readonly CollectionDescriptionGenerator $collections,
        private readonly SupportReplyGenerator $supportReplies,
        private readonly ZeroResultSuggestGenerator $zeroResultSuggest,
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
            'data' => $this->draftPayload($result),
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
            'data' => $this->draftPayload($result),
        ]);
    }

    public function generateCollectionDescription(Request $request): JsonResponse
    {
        $tenantId = $this->tenantId($request);

        if ($tenantId === null) {
            return response()->json(['message' => 'Tenant context required.'], Response::HTTP_FORBIDDEN);
        }

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'type' => ['nullable', 'string', 'max:32'],
            'rules' => ['nullable', 'array'],
        ]);

        try {
            $result = $this->collections->generate(
                $tenantId,
                $validated['title'],
                (string) ($validated['type'] ?? 'manual'),
                $validated['rules'] ?? null,
            );
        } catch (HttpException $exception) {
            return response()->json([
                'message' => $exception->getMessage(),
            ], $exception->getStatusCode());
        }

        return response()->json([
            'data' => $this->draftPayload($result),
        ]);
    }

    public function generateSupportReply(Request $request): JsonResponse
    {
        $tenantId = $this->tenantId($request);

        if ($tenantId === null) {
            return response()->json(['message' => 'Tenant context required.'], Response::HTTP_FORBIDDEN);
        }

        $validated = $request->validate([
            'order_number' => ['required', 'string', 'max:64'],
            'status' => ['required', 'string', 'max:32'],
            'total_kobo' => ['required', 'integer', 'min:0'],
            'items_summary' => ['required', 'string', 'max:500'],
            'question' => ['required', 'string', 'max:1000'],
        ]);

        try {
            $result = $this->supportReplies->generate($tenantId, [
                'order_number' => $validated['order_number'],
                'status' => $validated['status'],
                'total_kobo' => (int) $validated['total_kobo'],
                'items_summary' => $validated['items_summary'],
                'question' => $validated['question'],
            ]);
        } catch (HttpException $exception) {
            return response()->json([
                'message' => $exception->getMessage(),
            ], $exception->getStatusCode());
        }

        return response()->json([
            'data' => $this->draftPayload($result),
        ]);
    }

    public function generateZeroResultSuggest(Request $request): JsonResponse
    {
        $tenantId = $this->tenantId($request);

        if ($tenantId === null) {
            return response()->json(['message' => 'Tenant context required.'], Response::HTTP_FORBIDDEN);
        }

        $validated = $request->validate([
            'query' => ['required', 'string', 'max:255'],
            'search_count' => ['nullable', 'integer', 'min:1', 'max:100000'],
        ]);

        try {
            $result = $this->zeroResultSuggest->generate(
                $tenantId,
                $validated['query'],
                (int) ($validated['search_count'] ?? 1),
            );
        } catch (HttpException $exception) {
            return response()->json([
                'message' => $exception->getMessage(),
            ], $exception->getStatusCode());
        }

        return response()->json([
            'data' => $this->draftPayload($result),
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

    /**
     * @return array<string, mixed>
     */
    private function draftPayload(\Platform\Ai\Contracts\CompletionResult $result): array
    {
        return [
            'draft' => $result->text,
            'provider' => $result->provider,
            'model' => $result->model,
            'degraded' => $result->degraded,
            'requires_merchant_edit' => true,
            'watermark' => 'ai-generated-draft',
        ];
    }

    private function tenantId(Request $request): ?string
    {
        $tenantId = $request->attributes->get('tenant_id');

        return is_string($tenantId) && $tenantId !== '' ? $tenantId : null;
    }
}
