<?php

declare(strict_types=1);

namespace Modules\Commerce\Catalog\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Platform\Tenancy\Models\Concerns\BelongsToTenant;

final class ProductSearchQuery extends Model
{
    use BelongsToTenant;
    use HasUuids;

    public $incrementing = false;

    protected $keyType = 'string';

    protected $table = 'product_search_queries';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'tenant_id',
        'query',
        'results_count',
        'searched_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'tenant_id' => 'string',
            'results_count' => 'integer',
            'searched_at' => 'datetime',
        ];
    }
}
