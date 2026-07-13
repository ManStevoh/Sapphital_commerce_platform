<?php

declare(strict_types=1);

namespace Modules\Commerce\Orders\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Platform\Tenancy\Models\Concerns\BelongsToTenant;

final class Order extends Model
{
    use BelongsToTenant;
    use HasUuids;

    public const STATUS_PENDING = 'pending';

    public const STATUS_PAID = 'paid';

    public const STATUS_CANCELLED = 'cancelled';

    public const STATUS_FULFILLED = 'fulfilled';

    public const STATUS_REFUNDED = 'refunded';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $table = 'orders';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'tenant_id',
        'checkout_session_id',
        'order_number',
        'status',
        'currency',
        'subtotal_kobo',
        'total_kobo',
        'customer_email',
        'paystack_reference',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'tenant_id' => 'string',
            'checkout_session_id' => 'string',
            'subtotal_kobo' => 'integer',
            'total_kobo' => 'integer',
        ];
    }

    /**
     * @return HasMany<OrderItem, $this>
     */
    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    /**
     * @return BelongsTo<CheckoutSession, $this>
     */
    public function checkoutSession(): BelongsTo
    {
        return $this->belongsTo(\Modules\Commerce\Checkout\Models\CheckoutSession::class, 'checkout_session_id');
    }
}
