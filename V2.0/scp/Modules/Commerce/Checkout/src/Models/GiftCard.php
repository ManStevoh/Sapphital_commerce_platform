<?php

declare(strict_types=1);

namespace Modules\Commerce\Checkout\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Commerce\Checkout\Enums\GiftCardStatus;
use Platform\Tenancy\Models\Concerns\BelongsToTenant;

final class GiftCard extends Model
{
    use BelongsToTenant;
    use HasUuids;

    /** @var list<int> */
    public const PRESET_DENOMINATIONS_KOBO = [500_000, 1_000_000, 2_500_000];

    public $incrementing = false;

    protected $keyType = 'string';

    protected $table = 'gift_cards';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'tenant_id',
        'code',
        'initial_balance_kobo',
        'balance_kobo',
        'currency',
        'status',
        'expires_at',
        'purchaser_email',
        'recipient_email',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'tenant_id' => 'string',
            'initial_balance_kobo' => 'integer',
            'balance_kobo' => 'integer',
            'status' => GiftCardStatus::class,
            'expires_at' => 'datetime',
        ];
    }

    /**
     * @return HasMany<GiftCardTransaction, $this>
     */
    public function transactions(): HasMany
    {
        return $this->hasMany(GiftCardTransaction::class, 'gift_card_id');
    }

    public function isRedeemable(): bool
    {
        if ($this->status !== GiftCardStatus::Active) {
            return false;
        }

        if ($this->balance_kobo <= 0) {
            return false;
        }

        if ($this->expires_at !== null && $this->expires_at->isPast()) {
            return false;
        }

        return true;
    }
}
