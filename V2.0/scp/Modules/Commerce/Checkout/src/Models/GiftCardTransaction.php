<?php

declare(strict_types=1);

namespace Modules\Commerce\Checkout\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Platform\Tenancy\Models\Concerns\BelongsToTenant;

final class GiftCardTransaction extends Model
{
    use BelongsToTenant;
    use HasUuids;

    public const TYPE_ISSUE = 'issue';

    public const TYPE_REDEEM = 'redeem';

    public const TYPE_ADJUST = 'adjust';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $table = 'gift_card_transactions';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'tenant_id',
        'gift_card_id',
        'order_id',
        'checkout_session_id',
        'delta_kobo',
        'type',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'tenant_id' => 'string',
            'gift_card_id' => 'string',
            'order_id' => 'string',
            'checkout_session_id' => 'string',
            'delta_kobo' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<GiftCard, $this>
     */
    public function giftCard(): BelongsTo
    {
        return $this->belongsTo(GiftCard::class, 'gift_card_id');
    }
}
