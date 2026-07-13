<?php

declare(strict_types=1);

namespace Modules\Commerce\Catalog\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Modules\Commerce\Catalog\Enums\CollectionStatus;
use Modules\Commerce\Catalog\Enums\CollectionType;
use Modules\Commerce\Catalog\Models\Collection;
use Modules\Commerce\Catalog\Models\CollectionProduct;
use Modules\Commerce\Catalog\Models\Product;
use Modules\Commerce\Catalog\Services\CollectionProductResolver;
use Modules\Commerce\Catalog\Services\CollectionScheduleNormalizer;
use Symfony\Component\HttpFoundation\Response;

final class CollectionController
{
    public function __construct(
        private readonly CollectionProductResolver $resolver,
        private readonly CollectionScheduleNormalizer $scheduleNormalizer,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $tenantId = $this->tenantId($request);

        if ($tenantId === null) {
            return $this->missingTenantResponse();
        }

        $collections = Collection::query()
            ->where('tenant_id', $tenantId)
            ->orderBy('title')
            ->get();

        return response()->json([
            'data' => $collections->map(fn (Collection $c): array => $this->collectionPayload($c))->values(),
        ]);
    }

    public function published(Request $request): JsonResponse
    {
        $tenantId = $this->tenantId($request);

        if ($tenantId === null) {
            return $this->missingTenantResponse();
        }

        $collections = Collection::query()
            ->where('tenant_id', $tenantId)
            ->where('status', CollectionStatus::Published)
            ->orderBy('title')
            ->get()
            ->filter(fn (Collection $c): bool => $c->isLive())
            ->values();

        return response()->json([
            'data' => $collections->map(fn (Collection $c): array => $this->collectionPayload($c))->values(),
        ]);
    }

    public function bySlug(Request $request, string $slug): JsonResponse
    {
        $tenantId = $this->tenantId($request);

        if ($tenantId === null) {
            return $this->missingTenantResponse();
        }

        $collection = Collection::query()
            ->where('tenant_id', $tenantId)
            ->where('slug', $slug)
            ->first();

        if ($collection === null || ! $collection->isLive()) {
            return $this->notFoundResponse();
        }

        $limit = min(max((int) $request->integer('limit', 24), 1), 50);
        $cursor = $request->query('cursor');
        $cursor = is_string($cursor) && $cursor !== '' ? $cursor : null;

        $products = $this->resolver->resolve($collection, $limit, $cursor);
        $nextCursor = $products->count() === $limit ? $products->last()?->id : null;

        return response()->json([
            'data' => [
                'collection' => $this->collectionPayload($collection),
                'products' => $products->values(),
            ],
            'meta' => [
                'next_cursor' => $nextCursor,
            ],
        ]);
    }

    public function show(Request $request, string $id): JsonResponse
    {
        $tenantId = $this->tenantId($request);

        if ($tenantId === null) {
            return $this->missingTenantResponse();
        }

        $collection = $this->findTenantCollection($tenantId, $id);

        if ($collection === null) {
            return $this->notFoundResponse();
        }

        return response()->json([
            'data' => $this->collectionPayload($collection),
        ]);
    }

    public function products(Request $request, string $id): JsonResponse
    {
        $tenantId = $this->tenantId($request);

        if ($tenantId === null) {
            return $this->missingTenantResponse();
        }

        $collection = $this->findTenantCollection($tenantId, $id);

        if ($collection === null) {
            return $this->notFoundResponse();
        }

        $limit = min(max((int) $request->integer('limit', 24), 1), 50);
        $cursor = $request->query('cursor');
        $cursor = is_string($cursor) && $cursor !== '' ? $cursor : null;

        $products = $this->resolver->resolve($collection, $limit, $cursor);
        $nextCursor = $products->count() === $limit ? $products->last()?->id : null;

        return response()->json([
            'data' => $products->values(),
            'meta' => [
                'next_cursor' => $nextCursor,
            ],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $tenantId = $this->tenantId($request);

        if ($tenantId === null) {
            return $this->missingTenantResponse();
        }

        $validated = $this->validatePayload($request, $tenantId);
        $validated = $this->scheduleNormalizer->normalize($validated);
        $this->assertSmartRules($validated);

        $slug = $validated['slug'] ?? Str::slug($validated['title']);

        $collection = Collection::query()->create([
            'tenant_id' => $tenantId,
            'title' => $validated['title'],
            'slug' => $slug,
            'description' => $validated['description'] ?? null,
            'type' => $validated['type'],
            'rules_json' => $validated['rules_json'] ?? null,
            'sort_order' => $validated['sort_order'] ?? 'manual',
            'status' => $validated['status'],
            'published_at' => $validated['published_at'] ?? null,
            'starts_at' => $validated['starts_at'] ?? null,
            'ends_at' => $validated['ends_at'] ?? null,
        ]);

        if (
            $collection->type === CollectionType::Manual
            && isset($validated['product_ids'])
            && is_array($validated['product_ids'])
        ) {
            $this->syncManualProducts($collection, $validated['product_ids']);
        }

        return response()->json([
            'data' => $this->collectionPayload($collection->fresh()),
        ], Response::HTTP_CREATED);
    }

    public function update(Request $request, string $id): JsonResponse
    {
        $tenantId = $this->tenantId($request);

        if ($tenantId === null) {
            return $this->missingTenantResponse();
        }

        $collection = $this->findTenantCollection($tenantId, $id);

        if ($collection === null) {
            return $this->notFoundResponse();
        }

        $validated = $this->validatePayload($request, $tenantId, $collection->id);
        $validated = $this->scheduleNormalizer->normalize($validated);
        $this->assertSmartRules($validated);

        $collection->update([
            'title' => $validated['title'],
            'slug' => $validated['slug'] ?? $collection->slug,
            'description' => $validated['description'] ?? null,
            'type' => $validated['type'],
            'rules_json' => $validated['rules_json'] ?? null,
            'sort_order' => $validated['sort_order'] ?? $collection->sort_order,
            'status' => $validated['status'],
            'published_at' => $validated['published_at'] ?? null,
            'starts_at' => $validated['starts_at'] ?? null,
            'ends_at' => $validated['ends_at'] ?? null,
        ]);

        if (
            $collection->type === CollectionType::Manual
            && array_key_exists('product_ids', $validated)
            && is_array($validated['product_ids'])
        ) {
            $this->syncManualProducts($collection->fresh(), $validated['product_ids']);
        }

        return response()->json([
            'data' => $this->collectionPayload($collection->fresh()),
        ]);
    }

    public function destroy(Request $request, string $id): JsonResponse
    {
        $tenantId = $this->tenantId($request);

        if ($tenantId === null) {
            return $this->missingTenantResponse();
        }

        $collection = $this->findTenantCollection($tenantId, $id);

        if ($collection === null) {
            return $this->notFoundResponse();
        }

        $collection->delete();

        return response()->json(null, Response::HTTP_NO_CONTENT);
    }

    public function syncProducts(Request $request, string $id): JsonResponse
    {
        $tenantId = $this->tenantId($request);

        if ($tenantId === null) {
            return $this->missingTenantResponse();
        }

        $collection = $this->findTenantCollection($tenantId, $id);

        if ($collection === null) {
            return $this->notFoundResponse();
        }

        if ($collection->type !== CollectionType::Manual) {
            throw ValidationException::withMessages([
                'product_ids' => ['Products can only be synced for manual collections.'],
            ]);
        }

        $validated = $request->validate([
            'product_ids' => ['required', 'array', 'max:500'],
            'product_ids.*' => ['uuid'],
        ]);

        $this->syncManualProducts($collection, $validated['product_ids']);

        $products = $this->resolver->resolve($collection->fresh(), 50);

        return response()->json([
            'data' => $products->values(),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function validatePayload(Request $request, string $tenantId, ?string $ignoreId = null): array
    {
        $slugRule = Rule::unique('collections', 'slug')->where('tenant_id', $tenantId);

        if ($ignoreId !== null) {
            $slugRule = $slugRule->ignore($ignoreId);
        }

        return $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', $slugRule],
            'description' => ['nullable', 'string', 'max:5000'],
            'type' => ['required', Rule::in(['manual', 'smart'])],
            'rules_json' => ['nullable', 'array'],
            'sort_order' => ['sometimes', Rule::in(['manual', 'best_selling', 'newest', 'price_asc', 'price_desc'])],
            'status' => ['required', Rule::in(['draft', 'published', 'scheduled'])],
            'published_at' => ['nullable', 'date'],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date'],
            'product_ids' => ['sometimes', 'array', 'max:500'],
            'product_ids.*' => ['uuid'],
        ]);

        if (
            isset($validated['starts_at'], $validated['ends_at'])
            && $validated['starts_at'] !== null
            && $validated['ends_at'] !== null
            && strtotime((string) $validated['ends_at']) <= strtotime((string) $validated['starts_at'])
        ) {
            throw ValidationException::withMessages([
                'ends_at' => ['The end time must be after the start time.'],
            ]);
        }

        return $validated;
    }

    /**
     * @param  array<string, mixed>  $validated
     */
    private function assertSmartRules(array $validated): void
    {
        if (($validated['type'] ?? null) !== CollectionType::Smart->value) {
            return;
        }

        $rulesJson = $validated['rules_json'] ?? null;

        if (! is_array($rulesJson)) {
            throw ValidationException::withMessages([
                'rules_json' => ['Smart collections require rules_json.'],
            ]);
        }

        $preset = $rulesJson['preset'] ?? null;
        $rules = $rulesJson['rules'] ?? null;

        $allowedPresets = ['new_arrivals', 'on_sale', 'best_sellers'];

        if (is_string($preset) && in_array($preset, $allowedPresets, true)) {
            return;
        }

        if (! is_array($rules) || $rules === []) {
            throw ValidationException::withMessages([
                'rules_json' => ['Provide a preset (new_arrivals, on_sale, best_sellers) or a non-empty rules list.'],
            ]);
        }

        if (count($rules) > 10) {
            throw ValidationException::withMessages([
                'rules_json.rules' => ['A maximum of 10 rules is allowed.'],
            ]);
        }

        $allowedFields = ['tag', 'type', 'fulfillment_type', 'price_kobo', 'price_cents', 'created_at'];
        $allowedOperators = ['eq', 'contains', 'lte', 'gte', 'lt', 'gt'];

        foreach ($rules as $index => $rule) {
            if (! is_array($rule)) {
                throw ValidationException::withMessages([
                    "rules_json.rules.{$index}" => ['Each rule must be an object.'],
                ]);
            }

            $field = $rule['field'] ?? null;
            $operator = $rule['operator'] ?? null;

            if (! is_string($field) || ! in_array($field, $allowedFields, true)) {
                throw ValidationException::withMessages([
                    "rules_json.rules.{$index}.field" => ['Field is not allowed.'],
                ]);
            }

            if (! is_string($operator) || ! in_array($operator, $allowedOperators, true)) {
                throw ValidationException::withMessages([
                    "rules_json.rules.{$index}.operator" => ['Operator is not allowed.'],
                ]);
            }
        }
    }

    /**
     * @param  list<string>  $productIds
     */
    private function syncManualProducts(Collection $collection, array $productIds): void
    {
        $uniqueIds = array_values(array_unique($productIds));

        $validIds = Product::query()
            ->where('tenant_id', $collection->tenant_id)
            ->whereIn('id', $uniqueIds)
            ->pluck('id')
            ->all();

        if (count($validIds) !== count($uniqueIds)) {
            throw ValidationException::withMessages([
                'product_ids' => ['One or more products were not found in this tenant.'],
            ]);
        }

        CollectionProduct::query()
            ->where('collection_id', $collection->id)
            ->delete();

        foreach ($uniqueIds as $position => $productId) {
            CollectionProduct::query()->create([
                'tenant_id' => $collection->tenant_id,
                'collection_id' => $collection->id,
                'product_id' => $productId,
                'position' => $position,
            ]);
        }
    }

    private function findTenantCollection(string $tenantId, string $id): ?Collection
    {
        return Collection::query()
            ->where('tenant_id', $tenantId)
            ->where('id', $id)
            ->first();
    }

    /**
     * @return array<string, mixed>
     */
    private function collectionPayload(Collection $collection): array
    {
        return [
            'id' => $collection->id,
            'tenant_id' => $collection->tenant_id,
            'title' => $collection->title,
            'slug' => $collection->slug,
            'description' => $collection->description,
            'type' => $collection->type->value,
            'rules_json' => $collection->rules_json,
            'sort_order' => $collection->sort_order,
            'status' => $collection->status->value,
            'published_at' => $collection->published_at?->toIso8601String(),
            'starts_at' => $collection->starts_at?->toIso8601String(),
            'ends_at' => $collection->ends_at?->toIso8601String(),
            'created_at' => $collection->created_at?->toIso8601String(),
            'updated_at' => $collection->updated_at?->toIso8601String(),
        ];
    }

    private function notFoundResponse(): JsonResponse
    {
        return response()->json([
            'message' => 'Collection not found.',
        ], Response::HTTP_NOT_FOUND);
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
