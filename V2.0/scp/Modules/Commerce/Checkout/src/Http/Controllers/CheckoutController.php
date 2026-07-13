<?php

declare(strict_types=1);

namespace Modules\Commerce\Checkout\Http\Controllers;

use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Modules\Commerce\Checkout\Services\CheckoutService;
use Symfony\Component\HttpFoundation\Response;

final class CheckoutController
{
    public function __construct(
        private readonly CheckoutService $checkoutService,
    ) {}

    public function store(Request $request): JsonResponse
    {
        $tenantId = $this->tenantId($request);

        if ($tenantId === null) {
            return $this->missingTenantResponse();
        }

        $validated = $request->validate([
            'cart_id' => ['required', 'uuid'],
        ]);

        try {
            $session = $this->checkoutService->createSession(
                $tenantId,
                $validated['cart_id'],
            );
        } catch (ModelNotFoundException) {
            return response()->json([
                'message' => 'Cart not found.',
            ], Response::HTTP_NOT_FOUND);
        } catch (ValidationException $exception) {
            return response()->json([
                'message' => $exception->getMessage(),
                'errors' => $exception->errors(),
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        return response()->json([
            'data' => $this->sessionPayload($session),
        ], Response::HTTP_CREATED);
    }

    public function update(Request $request, string $id): JsonResponse
    {
        $tenantId = $this->tenantId($request);

        if ($tenantId === null) {
            return $this->missingTenantResponse();
        }

        $validated = $request->validate([
            'customer_email' => ['sometimes', 'email'],
            'customer_phone' => ['sometimes', 'string', 'max:32'],
            'shipping_rate_id' => ['sometimes', 'nullable', 'uuid'],
            'shipping_address' => ['sometimes', 'array'],
            'shipping_address.line1' => ['required_with:shipping_address', 'string', 'max:255'],
            'shipping_address.city' => ['required_with:shipping_address', 'string', 'max:120'],
            'shipping_address.state' => ['required_with:shipping_address', 'string', 'max:120'],
            'shipping_address.lga' => ['nullable', 'string', 'max:120'],
        ]);

        try {
            $session = $this->checkoutService->updateSession($tenantId, $id, $validated);
        } catch (ModelNotFoundException) {
            return response()->json([
                'message' => 'Checkout session not found.',
            ], Response::HTTP_NOT_FOUND);
        } catch (ValidationException $exception) {
            return response()->json([
                'message' => $exception->getMessage(),
                'errors' => $exception->errors(),
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        return response()->json([
            'data' => $this->sessionPayload($session),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function sessionPayload(\Modules\Commerce\Checkout\Models\CheckoutSession $session): array
    {
        return [
            'session_id' => $session->id,
            'total_kobo' => $session->total_kobo,
            'shipping_kobo' => $session->shipping_kobo,
            'gift_card_id' => $session->gift_card_id,
            'gift_card_applied_kobo' => $session->gift_card_applied_kobo,
            'status' => $session->status,
            'customer_email' => $session->customer_email,
            'customer_phone' => $session->customer_phone,
            'shipping_address' => $session->shipping_address,
            'shipping_rate_id' => $session->shipping_rate_id,
        ];
    }

    private function tenantId(Request $request): ?string
    {
        $tenantId = $request->attributes->get('tenant_id');

        if (! is_string($tenantId) || $tenantId === '') {
            return null;
        }

        return $tenantId;
    }

    private function missingTenantResponse(): JsonResponse
    {
        return response()->json([
            'message' => 'Tenant context required.',
        ], Response::HTTP_FORBIDDEN);
    }
}
