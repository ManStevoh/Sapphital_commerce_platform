<?php

declare(strict_types=1);

namespace Connectors\Paystack;

final class PaystackConnector implements PaystackConnectorInterface
{
    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function initializeTransaction(array $payload): array
    {
        $reference = is_string($payload['reference'] ?? null)
            ? $payload['reference']
            : 'stub_ref_'.uniqid();

        return [
            'status' => true,
            'message' => 'Authorization URL created',
            'data' => [
                'authorization_url' => 'https://checkout.paystack.com/stub',
                'access_code' => 'stub_access_code',
                'reference' => $reference,
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function verifyTransaction(string $reference): array
    {
        return [
            'status' => true,
            'message' => 'Verification successful',
            'data' => [
                'reference' => $reference,
                'status' => 'success',
            ],
        ];
    }

    public function verifyWebhookSignature(string $payload, string $signature): bool
    {
        if ($this->isStubMode()) {
            $decoded = json_decode($payload, true);

            return is_array($decoded) && ($decoded['event'] ?? null) === 'charge.success';
        }

        $secretKey = config('paystack.secret_key');

        if (! is_string($secretKey) || $secretKey === '') {
            return false;
        }

        $computed = hash_hmac('sha512', $payload, $secretKey);

        return hash_equals($computed, $signature);
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array{event: string, reference: string, amount: int, status: string}
     */
    public function handleWebhook(array $payload): array
    {
        $data = is_array($payload['data'] ?? null) ? $payload['data'] : [];
        $amount = $data['amount'] ?? 0;

        return [
            'event' => is_string($payload['event'] ?? null) ? $payload['event'] : '',
            'reference' => is_string($data['reference'] ?? null) ? $data['reference'] : '',
            'amount' => is_int($amount) ? $amount : (int) $amount,
            'status' => is_string($data['status'] ?? null) ? $data['status'] : '',
        ];
    }

    private function isStubMode(): bool
    {
        $secretKey = config('paystack.secret_key');

        if (is_string($secretKey) && $secretKey !== '') {
            return false;
        }

        return app()->environment('testing') || ! is_string($secretKey) || $secretKey === '';
    }
}
