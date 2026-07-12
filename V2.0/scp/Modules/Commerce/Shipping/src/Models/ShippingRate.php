<?php

declare(strict_types=1);

namespace Modules\Commerce\Shipping\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class ShippingRate extends Model
{
    use HasUuids;

    public const TYPE_FLAT = 'flat';

    public const TYPE_WEIGHT = 'weight';

    public const TYPE_PRICE = 'price';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $table = 'shipping_rates';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'zone_id',
        'name',
        'type',
        'min_order_kobo',
        'max_order_kobo',
        'price_kobo',
        'estimated_days_min',
        'estimated_days_max',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'zone_id' => 'string',
            'min_order_kobo' => 'integer',
            'max_order_kobo' => 'integer',
            'price_kobo' => 'integer',
            'estimated_days_min' => 'integer',
            'estimated_days_max' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<ShippingZone, $this>
     */
    public function zone(): BelongsTo
    {
        return $this->belongsTo(ShippingZone::class, 'zone_id');
    }
}
