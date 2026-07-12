<?php

declare(strict_types=1);

namespace Modules\Commerce\Shipping\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class Shipment extends Model
{
    use HasUuids;

    public const STATUS_PENDING = 'pending';

    public const STATUS_LABEL_CREATED = 'label_created';

    public const STATUS_IN_TRANSIT = 'in_transit';

    public const STATUS_DELIVERED = 'delivered';

    public const CARRIER_MANUAL = 'manual';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $table = 'shipments';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'tenant_id',
        'order_id',
        'status',
        'carrier',
        'tracking_number',
        'tracking_url',
        'weight_grams',
        'shipped_at',
        'delivered_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'tenant_id' => 'string',
            'order_id' => 'string',
            'weight_grams' => 'integer',
            'shipped_at' => 'datetime',
            'delivered_at' => 'datetime',
        ];
    }

    /**
     * @return HasMany<ShipmentLine, $this>
     */
    public function lines(): HasMany
    {
        return $this->hasMany(ShipmentLine::class);
    }
}
