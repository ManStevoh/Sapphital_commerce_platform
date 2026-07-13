<?php

declare(strict_types=1);

namespace Modules\Commerce\Catalog\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Modules\Commerce\Catalog\Models\Product;
use Modules\Commerce\Catalog\Models\ProductDigitalAsset;

final class DigitalAssetStorage
{
    public const DISK = 'local';

    public const MAX_BYTES = 52_428_800; // 50 MB Phase 2 default

    public function store(Product $product, UploadedFile $file, int $downloadLimit = 5): ProductDigitalAsset
    {
        if ($product->fulfillment_type !== 'digital') {
            throw ValidationException::withMessages([
                'product_id' => ['Digital assets can only be attached to digital products.'],
            ]);
        }

        if ($file->getSize() !== null && $file->getSize() > self::MAX_BYTES) {
            throw ValidationException::withMessages([
                'file' => ['Digital asset exceeds the 50MB limit.'],
            ]);
        }

        $extension = $file->getClientOriginalExtension();
        $key = sprintf(
            'digital/%s/%s/%s%s',
            $product->tenant_id,
            $product->id,
            Str::uuid()->toString(),
            $extension !== '' ? '.'.$extension : '',
        );

        Storage::disk(self::DISK)->put($key, $file->getContent());

        $existing = ProductDigitalAsset::query()
            ->where('product_id', $product->id)
            ->first();

        if ($existing !== null) {
            Storage::disk(self::DISK)->delete($existing->storage_key);
            $existing->update([
                'storage_key' => $key,
                'original_filename' => $file->getClientOriginalName(),
                'mime_type' => $file->getClientMimeType(),
                'byte_size' => (int) ($file->getSize() ?? 0),
                'download_limit' => max(1, min($downloadLimit, 100)),
            ]);

            return $existing->fresh();
        }

        return ProductDigitalAsset::query()->create([
            'tenant_id' => $product->tenant_id,
            'product_id' => $product->id,
            'storage_key' => $key,
            'original_filename' => $file->getClientOriginalName(),
            'mime_type' => $file->getClientMimeType(),
            'byte_size' => (int) ($file->getSize() ?? 0),
            'download_limit' => max(1, min($downloadLimit, 100)),
        ]);
    }
}
