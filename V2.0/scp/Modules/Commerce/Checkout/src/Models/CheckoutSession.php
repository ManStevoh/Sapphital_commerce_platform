<?php

declare(strict_types=1);

namespace Modules\Commerce\Checkout\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

final class CheckoutSession extends Model
{
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
            'shipping_address' => 'array',
            'shipping_rate_id' => 'string',
        ];
    }
}
