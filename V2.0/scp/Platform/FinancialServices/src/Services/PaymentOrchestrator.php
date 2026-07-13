<?php

declare(strict_types=1);

namespace Platform\FinancialServices\Services;

use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Modules\Commerce\Checkout\Models\CheckoutSession;

final class PaymentOrchestrator
{
    public function __construct(
        private readonly PaymentGatewayResolver $gatewayResolver,
        private readonly TenantPaymentProviderService $tenantPaymentProvider,
    ) {}

    /**
     * @return array{authorization_url: string, reference: string}
     *
     * @throws ModelNotFoundException
     * @throws ValidationException
     */
    public function initializeCheckoutPayment(
        string $tenantId,
        string $checkoutSessionId,
        string $email,
        ?string $provider = null,
    ): array {
        $session = CheckoutSession::query()
            ->where('tenant_id', $tenantId)
            ->where('id', $checkoutSessionId)
            ->firstOrFail();

        if ($session->status !== CheckoutSession::STATUS_PENDING) {
            throw ValidationException::withMessages([
                'checkout_session_id' => ['Checkout session is not available for payment.'],
            ]);
        }

        if ((int) $session->total_kobo === 0 && (int) ($session->gift_card_applied_kobo ?? 0) > 0) {
            return $this->initializeGiftCardOnlyCheckout($session);
        }

        $reference = 'scp_'.$session->id.'_'.Str::lower(Str::random(8));
        $resolvedProvider = $provider ?? $this->tenantPaymentProvider->forTenant($tenantId);
        $gateway = $this->gatewayResolver->resolveForTenant($tenantId, $resolvedProvider);

        $response = $gateway->initializePayment([
            'email' => $email,
            'amount' => $session->total_kobo,
            'reference' => $reference,
            'metadata' => [
                'tenant_id' => $tenantId,
                'checkout_session_id' => $session->id,
            ],
        ]);

        if (! ($response['status'] ?? false)) {
            throw ValidationException::withMessages([
                'payment' => [(string) ($response['message'] ?? 'Payment initialization failed.')],
            ]);
        }

        $data = is_array($response['data'] ?? null) ? $response['data'] : [];
        $authorizationUrl = $data['authorization_url'] ?? null;
        $gatewayReference = $data['reference'] ?? $reference;

        if (! is_string($authorizationUrl) || $authorizationUrl === '') {
            throw ValidationException::withMessages([
                'payment' => ['Payment gateway did not return an authorization URL.'],
            ]);
        }

        if (! is_string($gatewayReference) || $gatewayReference === '') {
            throw ValidationException::withMessages([
                'payment' => ['Payment gateway did not return a reference.'],
            ]);
        }

        $session->update([
            'paystack_reference' => $gatewayReference,
        ]);

        return [
            'authorization_url' => $authorizationUrl,
            'reference' => $gatewayReference,
        ];
    }

    /**
     * @return array{status: string, reference: string, checkout_session_id: string, order_id?: string}
     *
     * @throws ModelNotFoundException
     * @throws ValidationException
     */
    public function verifyCheckoutPayment(
        string $tenantId,
        string $reference,
        ?int $webhookAmountKobo = null,
        ?string $provider = null,
    ): array {
        $session = CheckoutSession::query()
            ->where('tenant_id', $tenantId)
            ->where('paystack_reference', $reference)
            ->firstOrFail();

        if ($session->status === CheckoutSession::STATUS_COMPLETED && str_starts_with($reference, 'gc_')) {
            $orderId = $session->order?->id;

            return [
                'status' => CheckoutSession::STATUS_COMPLETED,
                'reference' => $reference,
                'checkout_session_id' => $session->id,
                ...($orderId !== null ? ['order_id' => (string) $orderId] : []),
            ];
        }

        if ($webhookAmountKobo !== null && $webhookAmountKobo !== $session->total_kobo) {
            throw ValidationException::withMessages([
                'reference' => ['Webhook payment amount does not match checkout total.'],
            ]);
        }

        $gateway = $this->gatewayResolver->resolveForTenant(
            $tenantId,
            $provider ?? $this->tenantPaymentProvider->forTenant($tenantId),
        );

        $response = $gateway->verifyPayment($reference);

        if (! ($response['status'] ?? false)) {
            throw ValidationException::withMessages([
                'reference' => [(string) ($response['message'] ?? 'Payment verification failed.')],
            ]);
        }

        $data = is_array($response['data'] ?? null) ? $response['data'] : [];
        $paymentStatus = $data['status'] ?? null;

        if ($paymentStatus !== 'success') {
            $session->update([
                'status' => CheckoutSession::STATUS_FAILED,
            ]);

            throw ValidationException::withMessages([
                'reference' => ['Payment was not successful.'],
            ]);
        }

        $verifiedAmount = $data['amount'] ?? null;

        if (is_int($verifiedAmount) && $verifiedAmount !== $session->total_kobo) {
            throw ValidationException::withMessages([
                'reference' => ['Verified payment amount does not match checkout total.'],
            ]);
        }

        $orderId = $this->completeCheckout($session, $reference);

        $result = [
            'status' => CheckoutSession::STATUS_COMPLETED,
            'reference' => $reference,
            'checkout_session_id' => $session->id,
        ];

        if ($orderId !== null) {
            $result['order_id'] = $orderId;
        }

        return $result;
    }

    /**
     * @return array{authorization_url: string, reference: string}
     */
    private function initializeGiftCardOnlyCheckout(CheckoutSession $session): array
    {
        $reference = 'gc_'.$session->id.'_'.Str::lower(Str::random(8));
        $session->update([
            'paystack_reference' => $reference,
        ]);

        $this->completeCheckout($session->fresh(), $reference);

        return [
            'authorization_url' => '/checkout/success?reference='.urlencode($reference),
            'reference' => $reference,
        ];
    }

    private function completeCheckout(CheckoutSession $session, string $reference): ?string
    {
        if ($session->status === CheckoutSession::STATUS_COMPLETED) {
            return null;
        }

        $orderServiceClass = 'Modules\\Commerce\\Orders\\Services\\OrderService';

        if (class_exists($orderServiceClass)) {
            try {
                /** @var object $orderService */
                $orderService = app($orderServiceClass);

                if (method_exists($orderService, 'createFromCheckoutSession')
                    && method_exists($orderService, 'markPaid')) {
                    $order = $orderService->createFromCheckoutSession($session->fresh());
                    $paidOrder = $orderService->markPaid((string) $order->id, $reference);

                    $session->update([
                        'paystack_reference' => $reference,
                    ]);

                    return (string) $paidOrder->id;
                }
            } catch (\Throwable) {
                // Orders module unavailable — fall through to checkout-only completion.
            }
        }

        $session->update([
            'status' => CheckoutSession::STATUS_COMPLETED,
            'paystack_reference' => $reference,
        ]);

        return null;
    }
}
