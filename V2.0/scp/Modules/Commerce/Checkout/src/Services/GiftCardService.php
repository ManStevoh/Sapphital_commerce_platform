<?php

declare(strict_types=1);

namespace Modules\Commerce\Checkout\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Modules\Commerce\Checkout\Enums\GiftCardStatus;
use Modules\Commerce\Checkout\Models\CheckoutSession;
use Modules\Commerce\Checkout\Models\GiftCard;
use Modules\Commerce\Checkout\Models\GiftCardTransaction;

final class GiftCardService
{
    /**
     * @param  array{denomination_kobo: int, purchaser_email?: string|null, recipient_email?: string|null, expires_at?: string|null}  $input
     */
    public function issue(string $tenantId, array $input): GiftCard
    {
        $denomination = (int) $input['denomination_kobo'];

        if (! in_array($denomination, GiftCard::PRESET_DENOMINATIONS_KOBO, true)) {
            throw ValidationException::withMessages([
                'denomination_kobo' => ['Denomination must be one of ₦5,000, ₦10,000, or ₦25,000.'],
            ]);
        }

        return DB::transaction(function () use ($tenantId, $input, $denomination): GiftCard {
            $card = GiftCard::query()->create([
                'tenant_id' => $tenantId,
                'code' => $this->uniqueCode($tenantId),
                'initial_balance_kobo' => $denomination,
                'balance_kobo' => $denomination,
                'currency' => 'NGN',
                'status' => GiftCardStatus::Active,
                'expires_at' => $input['expires_at'] ?? now()->addYear(),
                'purchaser_email' => $input['purchaser_email'] ?? null,
                'recipient_email' => $input['recipient_email'] ?? null,
            ]);

            GiftCardTransaction::query()->create([
                'tenant_id' => $tenantId,
                'gift_card_id' => $card->id,
                'delta_kobo' => $denomination,
                'type' => GiftCardTransaction::TYPE_ISSUE,
            ]);

            return $card;
        });
    }

    public function applyToCheckout(CheckoutSession $session, string $code): CheckoutSession
    {
        if ($session->status !== CheckoutSession::STATUS_PENDING) {
            throw ValidationException::withMessages([
                'code' => ['Checkout session is not open for gift card redemption.'],
            ]);
        }

        $card = GiftCard::query()
            ->where('tenant_id', $session->tenant_id)
            ->where('code', strtoupper(trim($code)))
            ->first();

        if ($card === null) {
            throw ValidationException::withMessages([
                'code' => ['Gift card not found.'],
            ]);
        }

        if ($card->expires_at !== null && $card->expires_at->isPast()) {
            $card->update(['status' => GiftCardStatus::Expired]);

            throw ValidationException::withMessages([
                'code' => ['Gift card has expired.'],
            ]);
        }

        if (! $card->isRedeemable()) {
            throw ValidationException::withMessages([
                'code' => ['Gift card is not redeemable.'],
            ]);
        }

        // Restore prior application so re-entry / re-apply stays idempotent.
        $payableBefore = max(
            0,
            (int) $session->total_kobo + (int) ($session->gift_card_applied_kobo ?? 0),
        );
        $apply = min($card->balance_kobo, $payableBefore);

        if ($apply <= 0) {
            throw ValidationException::withMessages([
                'code' => ['Checkout total is already covered.'],
            ]);
        }

        $session->update([
            'gift_card_id' => $card->id,
            'gift_card_applied_kobo' => $apply,
            'total_kobo' => $payableBefore - $apply,
        ]);

        return $session->fresh();
    }

    public function finalizeRedemption(CheckoutSession $session, ?string $orderId): void
    {
        $amount = (int) ($session->gift_card_applied_kobo ?? 0);
        $giftCardId = $session->gift_card_id;

        if ($amount <= 0 || ! is_string($giftCardId) || $giftCardId === '') {
            return;
        }

        DB::transaction(function () use ($session, $orderId, $amount, $giftCardId): void {
            /** @var GiftCard $card */
            $card = GiftCard::query()->whereKey($giftCardId)->lockForUpdate()->firstOrFail();

            if ($card->balance_kobo < $amount) {
                throw ValidationException::withMessages([
                    'gift_card' => ['Gift card balance changed; choose another tender.'],
                ]);
            }

            $newBalance = $card->balance_kobo - $amount;
            $card->update([
                'balance_kobo' => $newBalance,
                'status' => $newBalance === 0 ? GiftCardStatus::Depleted : GiftCardStatus::Active,
            ]);

            GiftCardTransaction::query()->create([
                'tenant_id' => $session->tenant_id,
                'gift_card_id' => $card->id,
                'order_id' => $orderId,
                'checkout_session_id' => $session->id,
                'delta_kobo' => -$amount,
                'type' => GiftCardTransaction::TYPE_REDEEM,
            ]);
        });
    }

    public function expireDue(): int
    {
        $cards = GiftCard::query()
            ->where('status', GiftCardStatus::Active)
            ->whereNotNull('expires_at')
            ->where('expires_at', '<=', now())
            ->limit(500)
            ->get();

        foreach ($cards as $card) {
            $card->update(['status' => GiftCardStatus::Expired]);
        }

        return $cards->count();
    }

    public function disable(string $tenantId, string $id): GiftCard
    {
        $card = GiftCard::query()
            ->where('tenant_id', $tenantId)
            ->whereKey($id)
            ->firstOrFail();

        $card->update(['status' => GiftCardStatus::Disabled]);

        return $card->fresh();
    }

    private function uniqueCode(string $tenantId): string
    {
        do {
            $code = 'GC-'.Str::upper(Str::random(4)).'-'.Str::upper(Str::random(4));
        } while (
            GiftCard::query()
                ->where('tenant_id', $tenantId)
                ->where('code', $code)
                ->exists()
        );

        return $code;
    }
}
