<?php

declare(strict_types=1);

namespace Platform\Billing\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Platform\Billing\Enums\BillingPaymentIntentStatus;
use Platform\Tenancy\Models\Concerns\BelongsToTenant;

final class BillingPaymentIntent extends Model
{
    use BelongsToTenant;
    use HasUuids;

    public $incrementing = false;

    protected $keyType = 'string';

    protected $table = 'billing_payment_intents';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'tenant_id',
        'subscription_id',
        'paystack_reference',
        'amount_kobo',
        'status',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'tenant_id' => 'string',
            'subscription_id' => 'string',
            'amount_kobo' => 'integer',
            'status' => BillingPaymentIntentStatus::class,
        ];
    }
}
