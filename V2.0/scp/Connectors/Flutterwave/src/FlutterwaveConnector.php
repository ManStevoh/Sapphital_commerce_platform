<?php

declare(strict_types=1);

namespace Connectors\Flutterwave;

use Illuminate\Support\Facades\Http;

final class FlutterwaveConnector implements FlutterwaveConnectorInterface
{
    private const BASE_URL = 'https://api.flutterwave.com/v3';

    public function __construct(
        private readonly ?string $secretKeyOverride = null,
        private readonly ?string $secretHashOverride = null,
    ) {}

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function initializeTransaction(array $payload): array
    {
        if ($this->isStubMode()) {
            return $this->stubInitializeResponse($payload);
        }

        $amountKobo = (int) ($payload['amount'] ?? 0);
        $reference = is_string($payload['reference'] ?? null) ? $payload['reference'] : null;

        $response = Http::withToken($this->secretKey())
            ->acceptJson()
            ->timeout(10)
            ->post(self::BASE_URL.'/payments', [
                'tx_ref' => $reference,
                'amount' => $this->majorAmountFromKobo($amountKobo),
                'currency' => $payload['currency'] ?? 'NGN',
                'redirect_url' => $payload['redirect_url'] ?? config('app.url'),
                'customer' => [
                    'email' => $payload['email'] ?? '',
                ],
                'meta' => $payload['metadata'] ?? [],
            ]);

        if (! $response->ok()) {
            return [
                'status' => 'error',
                'message' => (string) ($response->json('message') ?? 'Flutterwave initialization failed.'),
            ];
        }

        /** @var array<string, mixed> $body */
        $body = $response->json();

        return $body;
    }

    /**
     * @return array<string, mixed>
     */
    public function verifyTransaction(string $reference): array
    {
        if ($this->isStubMode()) {
            return $this->stubVerifyResponse($reference);
        }

        $response = Http::withToken($this->secretKey())
            ->acceptJson()
            ->timeout(10)
            ->get(self::BASE_URL.'/transactions/verify_by_reference/'.urlencode($reference));

        if (! $response->ok()) {
            return [
                'status' => 'error',
                'message' => (string) ($response->json('message') ?? 'Flutterwave verification failed.'),
            ];
        }

        /** @var array<string, mixed> $body */
        $body = $response->json();

        return $body;
    }

    /**
     * @return array<string, mixed>
     */
    public function refundTransaction(string $transactionReference, int $amountKobo): array
    {
        if ($this->isStubMode()) {
            return $this->stubRefundResponse($transactionReference, $amountKobo);
        }

        $response = Http::withToken($this->secretKey())
            ->acceptJson()
            ->timeout(10)
            ->post(self::BASE_URL.'/transactions/'.$transactionReference.'/refund', [
                'amount' => $this->majorAmountFromKobo($amountKobo),
            ]);

        if (! $response->ok()) {
            return [
                'status' => 'error',
                'message' => (string) ($response->json('message') ?? 'Flutterwave refund failed.'),
            ];
        }

        /** @var array<string, mixed> $body */
        $body = $response->json();

        return $body;
    }

    public function verifyWebhookSignature(string $payload, string $signature): bool
    {
        $secretHash = $this->secretHash();

        if ($secretHash !== '') {
            return hash_equals($secretHash, $signature);
        }

        if ($this->isStubMode()) {
            $decoded = json_decode($payload, true);
            $event = is_array($decoded) ? ($decoded['event'] ?? null) : null;

            return is_string($event) && in_array($event, [
                'charge.completed',
            ], true);
        }

        return false;
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array{
     *     event: string,
     *     reference: string,
     *     amount: int,
     *     status: string,
     *     provider_case_id: string,
     *     currency: string
     * }
     */
    public function handleWebhook(array $payload): array
    {
        $data = is_array($payload['data'] ?? null) ? $payload['data'] : [];
        $amountMajor = $data['amount'] ?? $data['charged_amount'] ?? 0;
        $reference = is_string($data['tx_ref'] ?? null)
            ? $data['tx_ref']
            : (is_string($data['flw_ref'] ?? null) ? $data['flw_ref'] : '');
        $providerCaseId = $data['id'] ?? $data['flw_ref'] ?? null;

        return [
            'event' => is_string($payload['event'] ?? null) ? $payload['event'] : '',
            'reference' => $reference,
            'amount' => $this->koboFromMajor($amountMajor),
            'status' => is_string($data['status'] ?? null) ? $data['status'] : '',
            'provider_case_id' => $providerCaseId !== null ? (string) $providerCaseId : '',
            'currency' => is_string($data['currency'] ?? null) ? $data['currency'] : 'NGN',
        ];
    }

    private function isStubMode(): bool
    {
        if ($this->secretKeyOverride !== null && $this->secretKeyOverride !== '') {
            return false;
        }

        if (app()->environment('testing')) {
            return true;
        }

        return $this->secretKey() === '';
    }

    private function secretKey(): string
    {
        if ($this->secretKeyOverride !== null && $this->secretKeyOverride !== '') {
            return $this->secretKeyOverride;
        }

        $secretKey = config('flutterwave.secret_key');

        return is_string($secretKey) ? $secretKey : '';
    }

    private function secretHash(): string
    {
        if ($this->secretHashOverride !== null && $this->secretHashOverride !== '') {
            return $this->secretHashOverride;
        }

        $secretHash = config('flutterwave.secret_hash');

        return is_string($secretHash) ? $secretHash : '';
    }

    private function majorAmountFromKobo(int $amountKobo): float
    {
        return round($amountKobo / 100, 2);
    }

    private function koboFromMajor(mixed $amount): int
    {
        if (is_int($amount)) {
            return $amount * 100;
        }

        if (is_float($amount)) {
            return (int) round($amount * 100);
        }

        if (is_string($amount) && is_numeric($amount)) {
            return (int) round(((float) $amount) * 100);
        }

        return 0;
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    private function stubInitializeResponse(array $payload): array
    {
        $reference = is_string($payload['reference'] ?? null)
            ? $payload['reference']
            : 'fw_stub_'.uniqid();

        return [
            'status' => 'success',
            'message' => 'Hosted payment link generated',
            'data' => [
                'link' => 'https://checkout.flutterwave.com/stub',
                'tx_ref' => $reference,
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function stubVerifyResponse(string $reference): array
    {
        return [
            'status' => 'success',
            'message' => 'Transaction fetched successfully',
            'data' => [
                'tx_ref' => $reference,
                'status' => 'successful',
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function stubRefundResponse(string $transactionReference, int $amountKobo): array
    {
        return [
            'status' => 'success',
            'message' => 'Refund processed',
            'data' => [
                'id' => 'refund_'.substr(md5($transactionReference.$amountKobo), 0, 12),
                'tx_ref' => $transactionReference,
                'amount' => $this->majorAmountFromKobo($amountKobo),
                'status' => 'completed',
            ],
        ];
    }
}
