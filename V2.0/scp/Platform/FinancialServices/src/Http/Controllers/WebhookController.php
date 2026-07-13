<?php

declare(strict_types=1);

namespace Platform\FinancialServices\Http\Controllers;

use Platform\FinancialServices\Services\WebhookSignatureResolver;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Modules\Commerce\Checkout\Models\CheckoutSession;
use Platform\FinancialServices\Enums\DisputeStatus;
use Platform\FinancialServices\Services\DisputeService;
use Platform\FinancialServices\Services\PaymentOrchestrator;
use Platform\FinancialServices\Services\WebhookEventRecorder;
use Symfony\Component\HttpFoundation\Response;

final class WebhookController
{
    public function __construct(
        private readonly WebhookSignatureResolver $webhookSignatures,
        private readonly PaymentOrchestrator $paymentOrchestrator,
        private readonly WebhookEventRecorder $webhookEvents,
        private readonly DisputeService $disputes,
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        $payload = $request->getContent();
        $signature = $request->header('X-Paystack-Signature', '');

        if (! is_string($signature)) {
            $signature = '';
        }

        if (! $this->webhookSignatures->verifyPaystack($payload, $signature)) {
            return response()->json([
                'message' => 'Invalid signature.',
            ], Response::HTTP_UNAUTHORIZED);
        }

        $decoded = json_decode($payload, true);

        if (! is_array($decoded)) {
            return response()->json([
                'message' => 'Invalid payload.',
            ], Response::HTTP_BAD_REQUEST);
        }

        $event = $this->webhookSignatures->paystackConnectorForPayload($payload)->handleWebhook($decoded);
        $payloadHash = hash('sha256', $payload);

        if ($event['reference'] !== ''
            && $this->webhookEvents->hasBeenProcessed('paystack', $event['event'], $event['reference'])) {
            return response()->json([
                'received' => true,
                'duplicate' => true,
            ]);
        }

        if ($event['event'] === 'charge.success' && $event['reference'] !== '') {
            $handled = false;

            $session = CheckoutSession::query()
                ->where('paystack_reference', $event['reference'])
                ->first();

            if ($session !== null) {
                try {
                    $this->paymentOrchestrator->verifyCheckoutPayment(
                        $session->tenant_id,
                        $event['reference'],
                        $event['amount'] > 0 ? $event['amount'] : null,
                    );
                    $handled = true;
                } catch (ValidationException $exception) {
                    return response()->json([
                        'message' => collect($exception->errors())->flatten()->first()
                            ?? 'Payment reconciliation failed.',
                        'errors' => $exception->errors(),
                    ], Response::HTTP_UNPROCESSABLE_ENTITY);
                } catch (ModelNotFoundException) {
                    // Session removed — acknowledge to stop retries.
                }
            }

            if (! $handled && class_exists(\Platform\Billing\Services\BillingSubscriptionPaymentService::class)) {
                $handled = app(\Platform\Billing\Services\BillingSubscriptionPaymentService::class)
                    ->tryHandleChargeSuccess($event['reference'], $event['amount']);
            }

            if ($handled) {
                $this->webhookEvents->record('paystack', $event['event'], $event['reference'], $payloadHash);
            }
        }

        if (in_array($event['event'], ['charge.dispute.create', 'charge.dispute.remind'], true)) {
            $this->disputes->openFromPaystackWebhook(
                $event['reference'],
                $event['provider_case_id'] ?? '',
                $event['amount'],
                $event['currency'] ?? 'NGN',
            );

            if (($event['provider_case_id'] ?? '') !== '') {
                $this->webhookEvents->record(
                    'paystack',
                    $event['event'],
                    $event['provider_case_id'],
                    $payloadHash,
                );
            }
        }

        if ($event['event'] === 'charge.dispute.resolve') {
            $resolvedStatus = match ($event['status']) {
                'won' => DisputeStatus::Won,
                'lost' => DisputeStatus::Lost,
                'withdrawn' => DisputeStatus::Withdrawn,
                default => DisputeStatus::UnderReview,
            };

            $this->disputes->resolveFromPaystackWebhook(
                $event['provider_case_id'] ?? '',
                $resolvedStatus,
            );

            if (($event['provider_case_id'] ?? '') !== '') {
                $this->webhookEvents->record(
                    'paystack',
                    $event['event'],
                    $event['provider_case_id'],
                    $payloadHash,
                );
            }
        }

        return response()->json([
            'received' => true,
        ]);
    }
}
