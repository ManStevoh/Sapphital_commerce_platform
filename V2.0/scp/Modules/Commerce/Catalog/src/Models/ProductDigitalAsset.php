<?php

declare(strict_types=1);

namespace Modules\Commerce\Catalog\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Platform\Tenancy\Models\Concerns\BelongsToTenant;

final class ProductDigitalAsset extends Model
{
    use BelongsToTenant;
    use HasUuids;

    public $incrementing = false;

    protected $keyType = 'string';

    protected $table = 'product_digital_assets';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'tenant_id',
        'product_id',
        'storage_key',
        'original_filename',
        'mime_type',
        'byte_size',
        'download_limit',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'tenant_id' => 'string',
            'product_id' => 'string',
            'byte_size' => 'integer',
            'download_limit' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<Product, $this>
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id');
    }
}
