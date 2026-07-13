<?php

declare(strict_types=1);

namespace Modules\Commerce\Catalog\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Modules\Commerce\Catalog\Models\Product;
use Modules\Commerce\Catalog\Models\ProductDigitalAsset;
use Modules\Commerce\Catalog\Services\DigitalAssetStorage;
use Symfony\Component\HttpFoundation\Response;

final class DigitalAssetController
{
    public function __construct(
        private readonly DigitalAssetStorage $storage,
    ) {}

    public function show(Request $request, string $productId): JsonResponse
    {
        $tenantId = $this->tenantId($request);

        if ($tenantId === null) {
            return $this->missingTenantResponse();
        }

        $product = $this->findProduct($tenantId, $productId);

        if ($product === null) {
            return response()->json(['message' => 'Product not found.'], Response::HTTP_NOT_FOUND);
        }

        $asset = ProductDigitalAsset::query()
            ->where('tenant_id', $tenantId)
            ->where('product_id', $product->id)
            ->first();

        if ($asset === null) {
            return response()->json(['message' => 'Digital asset not found.'], Response::HTTP_NOT_FOUND);
        }

        return response()->json(['data' => $this->payload($asset)]);
    }

    public function store(Request $request, string $productId): JsonResponse
    {
        $tenantId = $this->tenantId($request);

        if ($tenantId === null) {
            return $this->missingTenantResponse();
        }

        $product = $this->findProduct($tenantId, $productId);

        if ($product === null) {
            return response()->json(['message' => 'Product not found.'], Response::HTTP_NOT_FOUND);
        }

        $validated = $request->validate([
            'file' => ['required', 'file', 'max:51200'],
            'download_limit' => ['sometimes', 'integer', 'min:1', 'max:100'],
        ]);

        try {
            $asset = $this->storage->store(
                $product,
                $validated['file'],
                (int) ($validated['download_limit'] ?? 5),
            );
        } catch (ValidationException $exception) {
            return response()->json([
                'message' => collect($exception->errors())->flatten()->first() ?? 'Upload failed.',
                'errors' => $exception->errors(),
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        return response()->json(['data' => $this->payload($asset)], Response::HTTP_CREATED);
    }

    /**
     * @return array<string, mixed>
     */
    private function payload(ProductDigitalAsset $asset): array
    {
        return [
            'id' => $asset->id,
            'product_id' => $asset->product_id,
            'original_filename' => $asset->original_filename,
            'mime_type' => $asset->mime_type,
            'byte_size' => $asset->byte_size,
            'download_limit' => $asset->download_limit,
        ];
    }

    private function findProduct(string $tenantId, string $productId): ?Product
    {
        return Product::query()
            ->where('tenant_id', $tenantId)
            ->where('id', $productId)
            ->first();
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
