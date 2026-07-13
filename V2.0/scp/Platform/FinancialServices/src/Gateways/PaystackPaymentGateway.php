<?php

declare(strict_types=1);

namespace Platform\FinancialServices\Gateways;

use Connectors\Paystack\PaystackConnectorInterface;
use Platform\FinancialServices\Contracts\PaymentGatewayInterface;

final class PaystackPaymentGateway implements PaymentGatewayInterface
{
    public function __construct(
        private readonly PaystackConnectorInterface $connector,
    ) {}

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function initializePayment(array $payload): array
    {
        return $this->connector->initializeTransaction($payload);
    }

    /**
     * @return array<string, mixed>
     */
    public function verifyPayment(string $reference): array
    {
        return $this->connector->verifyTransaction($reference);
    }

    /**
     * @return array<string, mixed>
     */
    public function refundPayment(string $transactionReference, int $amountKobo): array
    {
        return $this->connector->refundTransaction($transactionReference, $amountKobo);
    }
}
