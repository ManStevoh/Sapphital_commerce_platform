<?php

declare(strict_types=1);

namespace Modules\Commerce\Catalog\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Modules\Commerce\Catalog\Models\ProductSearchQuery;
use Modules\Commerce\Catalog\Models\ProductSearchSynonym;
use Modules\Commerce\Catalog\Services\ProductSearchService;
use Symfony\Component\HttpFoundation\Response;

final class SearchController
{
    public function __construct(
        private readonly ProductSearchService $searchService,
    ) {}

    public function search(Request $request): JsonResponse
    {
        $tenantId = $this->tenantId($request);

        if ($tenantId === null) {
            return $this->missingTenantResponse();
        }

        $validated = $request->validate([
            'q' => ['nullable', 'string', 'max:255'],
            'min_price_kobo' => ['nullable', 'integer', 'min:0'],
            'max_price_kobo' => ['nullable', 'integer', 'min:0'],
            'in_stock' => ['nullable', 'boolean'],
            'fulfillment_type' => ['nullable', Rule::in(['physical', 'digital'])],
            'tag' => ['nullable', 'string', 'max:64'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:50'],
        ]);

        if (
            isset($validated['min_price_kobo'], $validated['max_price_kobo'])
            && $validated['max_price_kobo'] < $validated['min_price_kobo']
        ) {
            return response()->json([
                'message' => 'max_price_kobo must be greater than or equal to min_price_kobo.',
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $result = $this->searchService->search($tenantId, $validated);

        return response()->json([
            'data' => $result['products']->values(),
            'meta' => [
                'query' => $result['query'],
                'results_count' => $result['results_count'],
                'facets' => $result['facets'],
            ],
        ]);
    }

    public function autocomplete(Request $request): JsonResponse
    {
        $tenantId = $this->tenantId($request);

        if ($tenantId === null) {
            return $this->missingTenantResponse();
        }

        $validated = $request->validate([
            'q' => ['required', 'string', 'max:255'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:15'],
        ]);

        $suggestions = $this->searchService->autocomplete(
            $tenantId,
            $validated['q'],
            (int) ($validated['limit'] ?? 8),
        );

        return response()->json([
            'data' => $suggestions->map(static fn ($product): array => [
                'id' => $product->id,
                'name' => $product->name,
                'slug' => $product->slug,
                'price_kobo' => $product->price_kobo,
            ])->values(),
            'meta' => [
                'query' => trim($validated['q']),
            ],
        ]);
    }

    public function analytics(Request $request): JsonResponse
    {
        $tenantId = $this->tenantId($request);

        if ($tenantId === null) {
            return $this->missingTenantResponse();
        }

        $since = now()->subDays(30);

        $topQueries = ProductSearchQuery::query()
            ->where('tenant_id', $tenantId)
            ->where('searched_at', '>=', $since)
            ->select('query', DB::raw('COUNT(*) as searches'), DB::raw('AVG(results_count) as avg_results'))
            ->groupBy('query')
            ->orderByDesc('searches')
            ->limit(25)
            ->get()
            ->map(static fn ($row): array => [
                'query' => $row->query,
                'searches' => (int) $row->searches,
                'avg_results' => round((float) $row->avg_results, 2),
            ]);

        $zeroResult = ProductSearchQuery::query()
            ->where('tenant_id', $tenantId)
            ->where('searched_at', '>=', $since)
            ->where('results_count', 0)
            ->select('query', DB::raw('COUNT(*) as searches'))
            ->groupBy('query')
            ->orderByDesc('searches')
            ->limit(25)
            ->get()
            ->map(static fn ($row): array => [
                'query' => $row->query,
                'searches' => (int) $row->searches,
            ]);

        return response()->json([
            'data' => [
                'top_queries' => $topQueries,
                'zero_result_queries' => $zeroResult,
                'window_days' => 30,
            ],
        ]);
    }

    public function synonymsIndex(Request $request): JsonResponse
    {
        $tenantId = $this->tenantId($request);

        if ($tenantId === null) {
            return $this->missingTenantResponse();
        }

        $synonyms = ProductSearchSynonym::query()
            ->where('tenant_id', $tenantId)
            ->orderBy('term')
            ->orderBy('synonym')
            ->get(['id', 'tenant_id', 'term', 'synonym', 'created_at', 'updated_at']);

        return response()->json(['data' => $synonyms]);
    }

    public function synonymsStore(Request $request): JsonResponse
    {
        $tenantId = $this->tenantId($request);

        if ($tenantId === null) {
            return $this->missingTenantResponse();
        }

        $validated = $request->validate([
            'term' => ['required', 'string', 'max:64'],
            'synonym' => ['required', 'string', 'max:64', 'different:term'],
        ]);

        $synonym = ProductSearchSynonym::query()->create([
            'tenant_id' => $tenantId,
            'term' => mb_strtolower(trim($validated['term'])),
            'synonym' => mb_strtolower(trim($validated['synonym'])),
        ]);

        return response()->json(['data' => $synonym], Response::HTTP_CREATED);
    }

    public function synonymsDestroy(Request $request, string $id): JsonResponse
    {
        $tenantId = $this->tenantId($request);

        if ($tenantId === null) {
            return $this->missingTenantResponse();
        }

        $synonym = ProductSearchSynonym::query()
            ->where('tenant_id', $tenantId)
            ->where('id', $id)
            ->first();

        if ($synonym === null) {
            return response()->json(['message' => 'Synonym not found.'], Response::HTTP_NOT_FOUND);
        }

        $synonym->delete();

        return response()->json(null, Response::HTTP_NO_CONTENT);
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
