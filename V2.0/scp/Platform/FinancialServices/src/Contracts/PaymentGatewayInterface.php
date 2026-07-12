<?php

declare(strict_types=1);

namespace Platform\FinancialServices\Contracts;

interface PaymentGatewayInterface
{
    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function initializePayment(array $payload): array;

    /**
     * @return array<string, mixed>
     */
    public function verifyPayment(string $reference): array;
}
