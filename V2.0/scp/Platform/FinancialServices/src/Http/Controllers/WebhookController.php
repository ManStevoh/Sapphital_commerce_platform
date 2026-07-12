<?php

declare(strict_types=1);

namespace Platform\FinancialServices\Http\Controllers;

use Connectors\Paystack\PaystackConnectorInterface;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Modules\Commerce\Checkout\Models\CheckoutSession;
use Platform\FinancialServices\Services\PaymentOrchestrator;
use Symfony\Component\HttpFoundation\Response;

final class WebhookController
{
    public function __construct(
        private readonly PaystackConnectorInterface $paystackConnector,
        private readonly PaymentOrchestrator $paymentOrchestrator,
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        $payload = $request->getContent();
        $signature = $request->header('X-Paystack-Signature', '');

        if (! is_string($signature)) {
            $signature = '';
        }

        if (! $this->paystackConnector->verifyWebhookSignature($payload, $signature)) {
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

        $event = $this->paystackConnector->handleWebhook($decoded);

        if ($event['event'] === 'charge.success' && $event['reference'] !== '') {
            $session = CheckoutSession::query()
                ->where('paystack_reference', $event['reference'])
                ->first();

            if ($session !== null) {
                try {
                    $this->paymentOrchestrator->verifyCheckoutPayment(
                        $session->tenant_id,
                        $event['reference'],
                    );
                } catch (ModelNotFoundException|ValidationException) {
                    // Acknowledge webhook even when reconciliation cannot proceed.
                }
            }
        }

        return response()->json([
            'received' => true,
        ]);
    }
}
