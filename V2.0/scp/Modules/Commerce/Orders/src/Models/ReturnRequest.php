<?php

declare(strict_types=1);

namespace Modules\Commerce\Orders\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Commerce\Orders\Enums\ReturnRequestStatus;
use Platform\Tenancy\Models\Concerns\BelongsToTenant;

final class ReturnRequest extends Model
{
    use BelongsToTenant;
    use HasUuids;

    public $incrementing = false;

    protected $keyType = 'string';

    protected $table = 'return_requests';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'tenant_id',
        'order_id',
        'status',
        'reason',
        'notes',
        'rejection_reason',
        'requested_at',
        'resolved_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'tenant_id' => 'string',
            'order_id' => 'string',
            'status' => ReturnRequestStatus::class,
            'requested_at' => 'datetime',
            'resolved_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Order, $this>
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * @return HasMany<ReturnLine, $this>
     */
    public function lines(): HasMany
    {
        return $this->hasMany(ReturnLine::class);
    }
}
