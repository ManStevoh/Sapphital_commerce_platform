<?php

declare(strict_types=1);

namespace Platform\FinancialServices\Services;

use Connectors\Flutterwave\FlutterwaveConnector;
use Connectors\Flutterwave\FlutterwaveConnectorInterface;
use Connectors\Paystack\PaystackConnector;
use Connectors\Paystack\PaystackConnectorInterface;

final class PaymentConnectorFactory
{
    public function __construct(
        private readonly TenantPaymentCredentialsService $credentials,
    ) {}

    public function paystackForTenant(string $tenantId): PaystackConnectorInterface
    {
        $secretKey = $this->credentials->secretKeyFor($tenantId, 'paystack');

        return new PaystackConnector(
            $secretKey !== '' ? $secretKey : null,
        );
    }

    public function flutterwaveForTenant(string $tenantId): FlutterwaveConnectorInterface
    {
        $secretKey = $this->credentials->secretKeyFor($tenantId, 'flutterwave');
        $secretHash = $this->credentials->webhookHashFor($tenantId, 'flutterwave');

        return new FlutterwaveConnector(
            $secretKey !== '' ? $secretKey : null,
            $secretHash !== '' ? $secretHash : null,
        );
    }

    public function platformPaystack(): PaystackConnectorInterface
    {
        return new PaystackConnector;
    }

    public function platformFlutterwave(): FlutterwaveConnectorInterface
    {
        return new FlutterwaveConnector;
    }
}
