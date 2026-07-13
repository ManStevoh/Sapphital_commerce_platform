<?php

declare(strict_types=1);

namespace Modules\Commerce\Catalog\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Modules\Commerce\Catalog\Models\Product;
use Platform\Billing\Services\EntitlementChecker;
use Symfony\Component\HttpFoundation\Response;

final class ProductController
{
    public function __construct(
        private readonly EntitlementChecker $entitlementChecker,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $tenantId = $this->tenantId($request);

        if ($tenantId === null) {
            return $this->missingTenantResponse();
        }

        $products = Product::query()
            ->where('tenant_id', $tenantId)
            ->orderBy('name')
            ->get([
                'id',
                'tenant_id',
                'name',
                'slug',
                'price_kobo',
                'status',
                'inventory_qty',
                'fulfillment_type',
                'tags',
            ]);

        return response()->json([
            'data' => $products,
        ]);
    }

    public function related(Request $request, string $id): JsonResponse
    {
        $tenantId = $this->tenantId($request);

        if ($tenantId === null) {
            return $this->missingTenantResponse();
        }

        $product = $this->findTenantProduct($tenantId, $id);

        if ($product === null) {
            return $this->notFoundResponse();
        }

        $tags = array_values(array_filter($product->tags ?? [], 'is_string'));
        $limit = min(max((int) $request->integer('limit', 4), 1), 12);

        $query = Product::query()
            ->where('tenant_id', $tenantId)
            ->where('status', 'published')
            ->where('id', '!=', $product->id);

        if ($tags !== []) {
            $query->where(function ($query) use ($tags): void {
                foreach ($tags as $tag) {
                    $query->orWhereJsonContains('tags', $tag);
                }
            });
        }

        $related = $query
            ->orderBy('name')
            ->limit($limit)
            ->get([
                'id',
                'tenant_id',
                'name',
                'slug',
                'price_kobo',
                'status',
                'inventory_qty',
                'fulfillment_type',
                'tags',
            ]);

        return response()->json(['data' => $related]);
    }

    public function store(Request $request): JsonResponse
    {
        $tenantId = $this->tenantId($request);

        if ($tenantId === null) {
            return $this->missingTenantResponse();
        }

        if (! $this->entitlementChecker->canAddProduct($tenantId)) {
            return response()->json([
                'message' => 'Product limit reached for current plan.',
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => [
                'nullable',
                'string',
                'max:255',
                Rule::unique('products', 'slug')->where('tenant_id', $tenantId),
            ],
            'price_kobo' => ['required', 'integer', 'min:0'],
            'status' => ['required', Rule::in(['draft', 'published'])],
            'inventory_qty' => ['required', 'integer', 'min:0'],
            'fulfillment_type' => ['sometimes', Rule::in(['physical', 'digital'])],
            'tags' => ['nullable', 'array', 'max:20'],
            'tags.*' => ['string', 'max:64'],
        ]);

        $slug = $validated['slug'] ?? Str::slug($validated['name']);

        $product = Product::query()->create([
            'tenant_id' => $tenantId,
            'name' => $validated['name'],
            'slug' => $slug,
            'price_kobo' => $validated['price_kobo'],
            'status' => $validated['status'],
            'inventory_qty' => $validated['inventory_qty'],
            'fulfillment_type' => $validated['fulfillment_type'] ?? 'physical',
            'tags' => array_values($validated['tags'] ?? []),
        ]);

        return response()->json([
            'data' => $this->productPayload($product),
        ], Response::HTTP_CREATED);
    }

    public function show(Request $request, string $id): JsonResponse
    {
        $tenantId = $this->tenantId($request);

        if ($tenantId === null) {
            return $this->missingTenantResponse();
        }

        $product = $this->findTenantProduct($tenantId, $id);

        if ($product === null) {
            return $this->notFoundResponse();
        }

        return response()->json([
            'data' => $this->productPayload($product),
        ]);
    }

    public function update(Request $request, string $id): JsonResponse
    {
        $tenantId = $this->tenantId($request);

        if ($tenantId === null) {
            return $this->missingTenantResponse();
        }

        $product = $this->findTenantProduct($tenantId, $id);

        if ($product === null) {
            return $this->notFoundResponse();
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => [
                'nullable',
                'string',
                'max:255',
                Rule::unique('products', 'slug')
                    ->where('tenant_id', $tenantId)
                    ->ignore($product->id),
            ],
            'price_kobo' => ['required', 'integer', 'min:0'],
            'status' => ['required', Rule::in(['draft', 'published'])],
            'inventory_qty' => ['required', 'integer', 'min:0'],
            'fulfillment_type' => ['sometimes', Rule::in(['physical', 'digital'])],
            'tags' => ['nullable', 'array', 'max:20'],
            'tags.*' => ['string', 'max:64'],
        ]);

        $product->update([
            'name' => $validated['name'],
            'slug' => $validated['slug'] ?? Str::slug($validated['name']),
            'price_kobo' => $validated['price_kobo'],
            'status' => $validated['status'],
            'inventory_qty' => $validated['inventory_qty'],
            'fulfillment_type' => $validated['fulfillment_type'] ?? $product->fulfillment_type,
            'tags' => array_key_exists('tags', $validated)
                ? array_values($validated['tags'] ?? [])
                : $product->tags,
        ]);

        return response()->json([
            'data' => $this->productPayload($product->fresh()),
        ]);
    }

    public function destroy(Request $request, string $id): JsonResponse
    {
        $tenantId = $this->tenantId($request);

        if ($tenantId === null) {
            return $this->missingTenantResponse();
        }

        $product = $this->findTenantProduct($tenantId, $id);

        if ($product === null) {
            return $this->notFoundResponse();
        }

        $product->delete();

        return response()->json(null, Response::HTTP_NO_CONTENT);
    }

    private function findTenantProduct(string $tenantId, string $id): ?Product
    {
        return Product::query()
            ->where('tenant_id', $tenantId)
            ->where('id', $id)
            ->first();
    }

    /**
     * @return array<string, mixed>
     */
    private function productPayload(Product $product): array
    {
        return $product->only([
            'id',
            'tenant_id',
            'name',
            'slug',
            'price_kobo',
            'status',
            'inventory_qty',
            'fulfillment_type',
            'tags',
        ]);
    }

    private function notFoundResponse(): JsonResponse
    {
        return response()->json([
            'message' => 'Product not found.',
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
