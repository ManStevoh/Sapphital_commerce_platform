<?php

declare(strict_types=1);

namespace Modules\Commerce\Catalog\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Commerce\Catalog\Enums\CollectionStatus;
use Modules\Commerce\Catalog\Enums\CollectionType;
use Platform\Tenancy\Models\Concerns\BelongsToTenant;

final class Collection extends Model
{
    use BelongsToTenant;
    use HasUuids;

    public $incrementing = false;

    protected $keyType = 'string';

    protected $table = 'collections';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'tenant_id',
        'title',
        'slug',
        'description',
        'type',
        'rules_json',
        'sort_order',
        'status',
        'published_at',
        'starts_at',
        'ends_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'tenant_id' => 'string',
            'type' => CollectionType::class,
            'rules_json' => 'array',
            'status' => CollectionStatus::class,
            'published_at' => 'datetime',
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
        ];
    }

    /**
     * @return HasMany<CollectionProduct, $this>
     */
    public function memberships(): HasMany
    {
        return $this->hasMany(CollectionProduct::class, 'collection_id')->orderBy('position');
    }

    public function isLive(?\Illuminate\Support\Carbon $asOf = null): bool
    {
        $asOf ??= now();

        if ($this->status !== CollectionStatus::Published) {
            return false;
        }

        if ($this->starts_at !== null && $this->starts_at->gt($asOf)) {
            return false;
        }

        if ($this->ends_at !== null && $this->ends_at->lte($asOf)) {
            return false;
        }

        return true;
    }
}
