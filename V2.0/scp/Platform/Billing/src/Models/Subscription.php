<?php

declare(strict_types=1);

namespace Platform\Billing\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Platform\Billing\Enums\SubscriptionStatus;
use Platform\Tenancy\Models\Concerns\BelongsToTenant;

final class Subscription extends Model
{
    use BelongsToTenant;
    use HasUuids;

    public $incrementing = false;

    protected $keyType = 'string';

    protected $table = 'subscriptions';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'tenant_id',
        'plan_id',
        'status',
        'trial_ends_at',
        'past_due_at',
        'current_period_end',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'tenant_id' => 'string',
            'plan_id' => 'string',
            'status' => SubscriptionStatus::class,
            'trial_ends_at' => 'datetime',
            'past_due_at' => 'datetime',
            'current_period_end' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Plan, $this>
     */
    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }
}
