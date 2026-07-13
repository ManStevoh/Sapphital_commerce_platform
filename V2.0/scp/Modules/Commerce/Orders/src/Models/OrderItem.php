<?php

declare(strict_types=1);

namespace Modules\Commerce\Orders\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class OrderItem extends Model
{
    use HasUuids;

    public $incrementing = false;

    protected $keyType = 'string';

    protected $table = 'order_items';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'order_id',
        'product_id',
        'product_name',
        'fulfillment_type',
        'quantity',
        'unit_price_kobo',
        'line_total_kobo',
        'downloaded_at',
        'download_count',
        'download_limit',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'order_id' => 'string',
            'product_id' => 'string',
            'quantity' => 'integer',
            'unit_price_kobo' => 'integer',
            'line_total_kobo' => 'integer',
            'downloaded_at' => 'datetime',
            'download_count' => 'integer',
            'download_limit' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<Order, $this>
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }
}
