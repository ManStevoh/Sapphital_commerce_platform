<?php

declare(strict_types=1);

namespace Platform\FinancialServices\Http\Controllers;

use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Platform\FinancialServices\Services\PaymentOrchestrator;
use Symfony\Component\HttpFoundation\Response;

final class PaymentController
{
    public function __construct(
        private readonly PaymentOrchestrator $paymentOrchestrator,
    ) {}

    public function initialize(Request $request): JsonResponse
    {
        $tenantId = $this->tenantId($request);

        if ($tenantId === null) {
            return $this->missingTenantResponse();
        }

        $validated = $request->validate([
            'checkout_session_id' => ['required', 'uuid'],
            'email' => ['required', 'email'],
            'provider' => ['sometimes', 'string', 'in:paystack,flutterwave'],
        ]);

        try {
            $result = $this->paymentOrchestrator->initializeCheckoutPayment(
                $tenantId,
                $validated['checkout_session_id'],
                $validated['email'],
                $validated['provider'] ?? null,
            );
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
            'data' => $result,
        ]);
    }

    public function verify(Request $request): JsonResponse
    {
        $tenantId = $this->tenantId($request);

        if ($tenantId === null) {
            return $this->missingTenantResponse();
        }

        $validated = $request->validate([
            'reference' => ['required', 'string', 'max:255'],
            'provider' => ['sometimes', 'string', 'in:paystack,flutterwave'],
        ]);

        try {
            $result = $this->paymentOrchestrator->verifyCheckoutPayment(
                $tenantId,
                $validated['reference'],
                null,
                $validated['provider'] ?? null,
            );
        } catch (ModelNotFoundException) {
            return response()->json([
                'message' => 'Checkout session not found for reference.',
            ], Response::HTTP_NOT_FOUND);
        } catch (ValidationException $exception) {
            return response()->json([
                'message' => $exception->getMessage(),
                'errors' => $exception->errors(),
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        return response()->json([
            'data' => $result,
        ]);
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
