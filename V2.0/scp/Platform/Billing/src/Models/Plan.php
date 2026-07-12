<?php

declare(strict_types=1);

namespace Platform\Billing\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class Plan extends Model
{
    use HasUuids;

    public $incrementing = false;

    protected $keyType = 'string';

    protected $table = 'plans';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'slug',
        'name',
        'price_ngn',
        'product_limit',
        'staff_limit',
        'custom_domain',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'price_ngn' => 'integer',
            'product_limit' => 'integer',
            'staff_limit' => 'integer',
            'custom_domain' => 'boolean',
        ];
    }

    /**
     * @return HasMany<Subscription, $this>
     */
    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }
}
