<?php

declare(strict_types=1);

namespace Modules\Commerce\Checkout\Http\Controllers;

use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Modules\Commerce\Checkout\Models\CheckoutSession;
use Modules\Commerce\Checkout\Models\GiftCard;
use Modules\Commerce\Checkout\Services\GiftCardService;
use Symfony\Component\HttpFoundation\Response;

final class GiftCardController
{
    public function __construct(
        private readonly GiftCardService $giftCards,
    ) {}

    public function store(Request $request): JsonResponse
    {
        $tenantId = $this->tenantId($request);

        if ($tenantId === null) {
            return $this->missingTenantResponse();
        }

        $validated = $request->validate([
            'denomination_kobo' => ['required', 'integer', Rule::in(GiftCard::PRESET_DENOMINATIONS_KOBO)],
            'purchaser_email' => ['nullable', 'email', 'max:255'],
            'recipient_email' => ['nullable', 'email', 'max:255'],
            'expires_at' => ['nullable', 'date', 'after:now'],
        ]);

        $card = $this->giftCards->issue($tenantId, $validated);

        return response()->json(['data' => $this->payload($card)], Response::HTTP_CREATED);
    }

    public function showByCode(Request $request, string $code): JsonResponse
    {
        $tenantId = $this->tenantId($request);

        if ($tenantId === null) {
            return $this->missingTenantResponse();
        }

        $card = GiftCard::query()
            ->where('tenant_id', $tenantId)
            ->where('code', strtoupper($code))
            ->first();

        if ($card === null) {
            return response()->json(['message' => 'Gift card not found.'], Response::HTTP_NOT_FOUND);
        }

        return response()->json(['data' => $this->payload($card)]);
    }

    public function disable(Request $request, string $id): JsonResponse
    {
        $tenantId = $this->tenantId($request);

        if ($tenantId === null) {
            return $this->missingTenantResponse();
        }

        try {
            $card = $this->giftCards->disable($tenantId, $id);
        } catch (ModelNotFoundException) {
            return response()->json(['message' => 'Gift card not found.'], Response::HTTP_NOT_FOUND);
        }

        return response()->json(['data' => $this->payload($card)]);
    }

    public function apply(Request $request, string $sessionId): JsonResponse
    {
        $tenantId = $this->tenantId($request);

        if ($tenantId === null) {
            return $this->missingTenantResponse();
        }

        $validated = $request->validate([
            'code' => ['required', 'string', 'max:32'],
        ]);

        $session = CheckoutSession::query()
            ->where('tenant_id', $tenantId)
            ->whereKey($sessionId)
            ->first();

        if ($session === null) {
            return response()->json(['message' => 'Checkout session not found.'], Response::HTTP_NOT_FOUND);
        }

        try {
            $session = $this->giftCards->applyToCheckout($session, $validated['code']);
        } catch (ValidationException $exception) {
            return response()->json([
                'message' => collect($exception->errors())->flatten()->first() ?? 'Unable to apply gift card.',
                'errors' => $exception->errors(),
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        return response()->json([
            'data' => [
                'session_id' => $session->id,
                'total_kobo' => $session->total_kobo,
                'shipping_kobo' => $session->shipping_kobo,
                'gift_card_id' => $session->gift_card_id,
                'gift_card_applied_kobo' => $session->gift_card_applied_kobo,
                'status' => $session->status,
            ],
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function payload(GiftCard $card): array
    {
        return [
            'id' => $card->id,
            'code' => $card->code,
            'initial_balance_kobo' => $card->initial_balance_kobo,
            'balance_kobo' => $card->balance_kobo,
            'currency' => $card->currency,
            'status' => $card->status->value,
            'expires_at' => $card->expires_at?->toIso8601String(),
            'purchaser_email' => $card->purchaser_email,
            'recipient_email' => $card->recipient_email,
        ];
    }

    private function tenantId(Request $request): ?string
    {
        $tenantId = $request->attributes->get('tenant_id');

        return is_string($tenantId) && $tenantId !== '' ? $tenantId : null;
    }

    private function missingTenantResponse(): JsonResponse
    {
        return response()->json([
            'message' => 'Tenant context required.',
        ], Response::HTTP_FORBIDDEN);
    }
}
