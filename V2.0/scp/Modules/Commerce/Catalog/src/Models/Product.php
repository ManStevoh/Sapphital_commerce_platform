<?php

declare(strict_types=1);

namespace Modules\Commerce\Catalog\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Platform\Tenancy\Models\Concerns\BelongsToTenant;

final class Product extends Model
{
    use BelongsToTenant;
    use HasUuids;

    public $incrementing = false;

    protected $keyType = 'string';

    protected $table = 'products';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'tenant_id',
        'name',
        'slug',
        'price_kobo',
        'status',
        'inventory_qty',
        'fulfillment_type',
        'tags',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'tenant_id' => 'string',
            'price_kobo' => 'integer',
            'inventory_qty' => 'integer',
            'tags' => 'array',
        ];
    }
}
