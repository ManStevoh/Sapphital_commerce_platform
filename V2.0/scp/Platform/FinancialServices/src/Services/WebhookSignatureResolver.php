<?php

declare(strict_types=1);

namespace Platform\FinancialServices\Services;

use Modules\Commerce\Checkout\Models\CheckoutSession;

final class WebhookSignatureResolver
{
    public function __construct(
        private readonly PaymentConnectorFactory $connectorFactory,
    ) {}

    public function verifyPaystack(string $payload, string $signature): bool
    {
        $tenantId = $this->resolveTenantIdFromPaystackPayload($payload);

        if ($tenantId !== null) {
            return $this->connectorFactory->paystackForTenant($tenantId)
                ->verifyWebhookSignature($payload, $signature);
        }

        return $this->connectorFactory->platformPaystack()->verifyWebhookSignature($payload, $signature);
    }

    public function paystackConnectorForPayload(string $payload): \Connectors\Paystack\PaystackConnectorInterface
    {
        $tenantId = $this->resolveTenantIdFromPaystackPayload($payload);

        if ($tenantId !== null) {
            return $this->connectorFactory->paystackForTenant($tenantId);
        }

        return $this->connectorFactory->platformPaystack();
    }

    public function verifyFlutterwave(string $payload, string $signature): bool
    {
        $tenantId = $this->resolveTenantIdFromFlutterwavePayload($payload);

        if ($tenantId !== null) {
            return $this->connectorFactory->flutterwaveForTenant($tenantId)
                ->verifyWebhookSignature($payload, $signature);
        }

        return $this->connectorFactory->platformFlutterwave()->verifyWebhookSignature($payload, $signature);
    }

    public function flutterwaveConnectorForPayload(string $payload): \Connectors\Flutterwave\FlutterwaveConnectorInterface
    {
        $tenantId = $this->resolveTenantIdFromFlutterwavePayload($payload);

        if ($tenantId !== null) {
            return $this->connectorFactory->flutterwaveForTenant($tenantId);
        }

        return $this->connectorFactory->platformFlutterwave();
    }

    private function resolveTenantIdFromPaystackPayload(string $payload): ?string
    {
        $decoded = json_decode($payload, true);

        if (! is_array($decoded)) {
            return null;
        }

        $data = is_array($decoded['data'] ?? null) ? $decoded['data'] : [];
        $transaction = is_array($data['transaction'] ?? null) ? $data['transaction'] : [];
        $reference = is_string($data['reference'] ?? null)
            ? $data['reference']
            : (is_string($transaction['reference'] ?? null) ? $transaction['reference'] : '');

        if ($reference === '') {
            return null;
        }

        return $this->tenantIdForPaymentReference($reference);
    }

    private function resolveTenantIdFromFlutterwavePayload(string $payload): ?string
    {
        $decoded = json_decode($payload, true);

        if (! is_array($decoded)) {
            return null;
        }

        $data = is_array($decoded['data'] ?? null) ? $decoded['data'] : [];
        $reference = is_string($data['tx_ref'] ?? null) ? $data['tx_ref'] : '';

        if ($reference === '') {
            return null;
        }

        return $this->tenantIdForPaymentReference($reference);
    }

    private function tenantIdForPaymentReference(string $reference): ?string
    {
        $session = CheckoutSession::query()
            ->where('paystack_reference', $reference)
            ->first();

        if ($session !== null) {
            return $session->tenant_id;
        }

        if (class_exists(\Platform\Billing\Services\BillingSubscriptionPaymentService::class)) {
            $tenantId = app(\Platform\Billing\Services\BillingSubscriptionPaymentService::class)
                ->tenantIdForPaymentReference($reference);

            if (is_string($tenantId) && $tenantId !== '') {
                return $tenantId;
            }
        }

        return null;
    }
}
