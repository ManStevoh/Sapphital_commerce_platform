<?php

declare(strict_types=1);

namespace Platform\FinancialServices\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Platform\FinancialServices\Enums\DisputeStatus;
use Platform\Tenancy\Models\Concerns\BelongsToTenant;

final class Dispute extends Model
{
    use BelongsToTenant;
    use HasUuids;

    public $incrementing = false;

    protected $keyType = 'string';

    protected $table = 'disputes';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'tenant_id',
        'order_id',
        'type',
        'provider',
        'status',
        'provider_case_id',
        'amount_kobo',
        'currency',
        'paystack_reference',
        'due_at',
        'deadline_alerted_at',
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
            'status' => DisputeStatus::class,
            'amount_kobo' => 'integer',
            'due_at' => 'datetime',
            'deadline_alerted_at' => 'datetime',
            'resolved_at' => 'datetime',
        ];
    }

    public static function hasOpenDisputeForOrder(string $tenantId, string $orderId): bool
    {
        return self::query()
            ->where('tenant_id', $tenantId)
            ->where('order_id', $orderId)
            ->whereIn('status', [
                DisputeStatus::Open,
                DisputeStatus::UnderReview,
            ])
            ->exists();
    }
}
