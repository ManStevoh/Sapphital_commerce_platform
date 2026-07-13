<?php

declare(strict_types=1);

namespace Platform\Billing\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Platform\Billing\Services\BillingSubscriptionPaymentService;
use Symfony\Component\HttpFoundation\Response;

final class InitializeSubscriptionPaymentController
{
    public function __construct(
        private readonly BillingSubscriptionPaymentService $payments,
    ) {}

    public function __invoke(Request $request, string $tenantId): JsonResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'email', 'max:255'],
        ]);

        try {
            $result = $this->payments->initializePayment(
                $tenantId,
                $validated['email'],
            );
        } catch (ValidationException $exception) {
            return response()->json([
                'message' => collect($exception->errors())->flatten()->first()
                    ?? 'Payment initialization failed.',
                'errors' => $exception->errors(),
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        return response()->json([
            'data' => $result,
        ]);
    }
}
