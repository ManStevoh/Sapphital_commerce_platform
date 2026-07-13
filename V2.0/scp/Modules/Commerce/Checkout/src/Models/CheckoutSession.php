<?php

declare(strict_types=1);

namespace Modules\Commerce\Checkout\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Platform\Tenancy\Models\Concerns\BelongsToTenant;

final class CheckoutSession extends Model
{
    use BelongsToTenant;
    use HasUuids;

    public const STATUS_PENDING = 'pending';

    public const STATUS_COMPLETED = 'completed';

    public const STATUS_FAILED = 'failed';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $table = 'checkout_sessions';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'tenant_id',
        'cart_id',
        'status',
        'total_kobo',
        'customer_email',
        'customer_phone',
        'shipping_address',
        'shipping_rate_id',
        'shipping_kobo',
        'gift_card_id',
        'gift_card_applied_kobo',
        'paystack_reference',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'tenant_id' => 'string',
            'cart_id' => 'string',
            'total_kobo' => 'integer',
            'shipping_kobo' => 'integer',
            'gift_card_id' => 'string',
            'gift_card_applied_kobo' => 'integer',
            'shipping_address' => 'array',
            'shipping_rate_id' => 'string',
        ];
    }

    /**
     * @return HasOne<\Modules\Commerce\Orders\Models\Order, $this>
     */
    public function order(): HasOne
    {
        return $this->hasOne(\Modules\Commerce\Orders\Models\Order::class, 'checkout_session_id');
    }
}
