<?php

declare(strict_types=1);

namespace Modules\Commerce\Checkout\Services;

use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Validation\ValidationException;
use Modules\Commerce\Cart\Models\Cart;
use Modules\Commerce\Checkout\Models\CheckoutSession;
use Modules\Commerce\Checkout\Models\GiftCard;
use Modules\Commerce\Shipping\Models\ShippingRate;

final class CheckoutService
{
    /**
     * @throws ModelNotFoundException
     * @throws ValidationException
     */
    public function createSession(string $tenantId, string $cartId): CheckoutSession
    {
        $cart = Cart::query()
            ->where('tenant_id', $tenantId)
            ->where('id', $cartId)
            ->with('items')
            ->firstOrFail();

        if ($cart->items->isEmpty()) {
            throw ValidationException::withMessages([
                'cart_id' => ['Cart must contain at least one item.'],
            ]);
        }

        $totalKobo = (int) $cart->items->sum('line_total_kobo');

        return CheckoutSession::query()->create([
            'tenant_id' => $tenantId,
            'cart_id' => $cart->id,
            'status' => CheckoutSession::STATUS_PENDING,
            'total_kobo' => $totalKobo,
            'shipping_kobo' => 0,
        ]);
    }

    /**
     * @param  array<string, mixed>  $input
     *
     * @throws ModelNotFoundException
     * @throws ValidationException
     */
    public function updateSession(string $tenantId, string $sessionId, array $input): CheckoutSession
    {
        $session = CheckoutSession::query()
            ->where('tenant_id', $tenantId)
            ->where('id', $sessionId)
            ->firstOrFail();

        if ($session->status !== CheckoutSession::STATUS_PENDING) {
            throw ValidationException::withMessages([
                'session_id' => ['Checkout session can no longer be updated.'],
            ]);
        }

        $cart = Cart::query()
            ->where('tenant_id', $tenantId)
            ->where('id', $session->cart_id)
            ->with('items')
            ->firstOrFail();

        $subtotalKobo = (int) $cart->items->sum('line_total_kobo');
        $shippingKobo = 0;
        $shippingRateId = $input['shipping_rate_id'] ?? $session->shipping_rate_id;

        if (is_string($shippingRateId) && $shippingRateId !== '') {
            $rate = ShippingRate::query()
                ->where('id', $shippingRateId)
                ->whereHas('zone', static fn ($query) => $query->where('tenant_id', $tenantId))
                ->first();

            if ($rate === null) {
                throw ValidationException::withMessages([
                    'shipping_rate_id' => ['Selected shipping rate is invalid.'],
                ]);
            }

            $shippingKobo = max(0, (int) $rate->price_kobo);
        }

        $baseTotal = $subtotalKobo + $shippingKobo;
        $giftCardId = $session->gift_card_id;
        $giftApplied = 0;

        if (is_string($giftCardId) && $giftCardId !== '') {
            $card = GiftCard::query()
                ->where('tenant_id', $tenantId)
                ->whereKey($giftCardId)
                ->first();

            if ($card !== null && $card->isRedeemable()) {
                $giftApplied = min($card->balance_kobo, $baseTotal);
            } else {
                $giftCardId = null;
            }
        }

        $session->update([
            'customer_email' => $input['customer_email'] ?? $session->customer_email,
            'customer_phone' => $input['customer_phone'] ?? $session->customer_phone,
            'shipping_address' => $input['shipping_address'] ?? $session->shipping_address,
            'shipping_rate_id' => is_string($shippingRateId) && $shippingRateId !== '' ? $shippingRateId : null,
            'shipping_kobo' => $shippingKobo,
            'gift_card_id' => $giftCardId,
            'gift_card_applied_kobo' => $giftApplied,
            'total_kobo' => $baseTotal - $giftApplied,
        ]);

        return $session->fresh();
    }
}
