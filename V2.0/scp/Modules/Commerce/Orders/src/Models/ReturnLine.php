<?php

declare(strict_types=1);

namespace Modules\Commerce\Orders\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class ReturnLine extends Model
{
    use HasUuids;

    public $incrementing = false;

    protected $keyType = 'string';

    protected $table = 'return_lines';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'return_request_id',
        'order_item_id',
        'quantity',
        'restock',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'return_request_id' => 'string',
            'order_item_id' => 'string',
            'quantity' => 'integer',
            'restock' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<ReturnRequest, $this>
     */
    public function returnRequest(): BelongsTo
    {
        return $this->belongsTo(ReturnRequest::class);
    }

    /**
     * @return BelongsTo<OrderItem, $this>
     */
    public function orderItem(): BelongsTo
    {
        return $this->belongsTo(OrderItem::class);
    }
}
