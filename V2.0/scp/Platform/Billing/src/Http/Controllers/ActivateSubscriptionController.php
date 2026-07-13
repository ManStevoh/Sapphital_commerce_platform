<?php

declare(strict_types=1);

namespace Platform\Billing\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Platform\Billing\Services\SubscriptionLifecycleService;
use Symfony\Component\HttpFoundation\Response;

final class ActivateSubscriptionController
{
    public function __construct(
        private readonly SubscriptionLifecycleService $lifecycle,
    ) {}

    public function __invoke(Request $request, string $tenantId): JsonResponse
    {
        $validated = $request->validate([
            'paystack_reference' => ['nullable', 'string', 'max:255'],
        ]);

        try {
            $result = $this->lifecycle->activateAfterPayment(
                $tenantId,
                $validated['paystack_reference'] ?? null,
            );
        } catch (ValidationException $exception) {
            return response()->json([
                'message' => collect($exception->errors())->flatten()->first()
                    ?? 'Subscription activation failed.',
                'errors' => $exception->errors(),
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $subscription = $result['subscription'];
        $invoice = $result['invoice'];

        return response()->json([
            'data' => [
                'subscription' => [
                    'id' => $subscription->id,
                    'tenant_id' => $subscription->tenant_id,
                    'status' => $subscription->status->value,
                    'current_period_end' => $subscription->current_period_end?->toIso8601String(),
                    'plan' => $subscription->plan === null ? null : [
                        'slug' => $subscription->plan->slug,
                        'name' => $subscription->plan->name,
                    ],
                ],
                'invoice' => $invoice === null ? null : [
                    'id' => $invoice->id,
                    'number' => $invoice->number,
                    'status' => $invoice->status->value,
                    'total' => $invoice->total,
                    'currency' => $invoice->currency,
                ],
            ],
        ]);
    }
}
