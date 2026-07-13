<?php

declare(strict_types=1);

namespace Modules\Commerce\Cart\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Platform\Tenancy\Models\Concerns\BelongsToTenant;

final class Cart extends Model
{
    use BelongsToTenant;
    use HasUuids;

    public $incrementing = false;

    protected $keyType = 'string';

    protected $table = 'carts';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'tenant_id',
        'session_id',
        'customer_id',
        'currency',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'tenant_id' => 'string',
            'customer_id' => 'string',
        ];
    }

    /**
     * @return HasMany<CartItem, $this>
     */
    public function items(): HasMany
    {
        return $this->hasMany(CartItem::class);
    }
}
