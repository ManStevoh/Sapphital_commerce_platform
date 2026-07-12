<?php

declare(strict_types=1);

namespace Connectors\Paystack;

interface PaystackConnectorInterface
{
    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function initializeTransaction(array $payload): array;

    /**
     * @return array<string, mixed>
     */
    public function verifyTransaction(string $reference): array;

    public function verifyWebhookSignature(string $payload, string $signature): bool;

    /**
     * @param  array<string, mixed>  $payload
     * @return array{event: string, reference: string, amount: int, status: string}
     */
    public function handleWebhook(array $payload): array;
}
