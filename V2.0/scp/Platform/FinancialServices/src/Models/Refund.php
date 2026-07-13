<?php

declare(strict_types=1);

namespace Platform\FinancialServices\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Platform\FinancialServices\Enums\RefundStatus;
use Platform\Tenancy\Models\Concerns\BelongsToTenant;

final class Refund extends Model
{
    use BelongsToTenant;
    use HasUuids;

    public $incrementing = false;

    protected $keyType = 'string';

    protected $table = 'refunds';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'tenant_id',
        'order_id',
        'amount_kobo',
        'currency',
        'status',
        'reason',
        'paystack_reference',
        'gateway_refund_reference',
        'processed_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'tenant_id' => 'string',
            'order_id' => 'string',
            'amount_kobo' => 'integer',
            'status' => RefundStatus::class,
            'processed_at' => 'datetime',
        ];
    }
}
