<?php

declare(strict_types=1);

namespace Platform\FinancialServices\Gateways;

use Connectors\Flutterwave\FlutterwaveConnectorInterface;
use Platform\FinancialServices\Contracts\PaymentGatewayInterface;

final class FlutterwavePaymentGateway implements PaymentGatewayInterface
{
    public function __construct(
        private readonly FlutterwaveConnectorInterface $connector,
    ) {}

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function initializePayment(array $payload): array
    {
        $response = $this->connector->initializeTransaction($payload);

        if (($response['status'] ?? '') !== 'success') {
            return [
                'status' => false,
                'message' => (string) ($response['message'] ?? 'Flutterwave initialization failed.'),
            ];
        }

        $data = is_array($response['data'] ?? null) ? $response['data'] : [];
        $reference = is_string($data['tx_ref'] ?? null)
            ? $data['tx_ref']
            : (is_string($payload['reference'] ?? null) ? $payload['reference'] : '');
        $authorizationUrl = is_string($data['link'] ?? null) ? $data['link'] : '';

        return [
            'status' => true,
            'message' => (string) ($response['message'] ?? 'Hosted payment link generated'),
            'data' => [
                'authorization_url' => $authorizationUrl,
                'reference' => $reference,
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function verifyPayment(string $reference): array
    {
        $response = $this->connector->verifyTransaction($reference);

        if (($response['status'] ?? '') !== 'success') {
            return [
                'status' => false,
                'message' => (string) ($response['message'] ?? 'Flutterwave verification failed.'),
            ];
        }

        $data = is_array($response['data'] ?? null) ? $response['data'] : [];
        $status = is_string($data['status'] ?? null) ? $data['status'] : '';

        return [
            'status' => true,
            'message' => (string) ($response['message'] ?? 'Verification successful'),
            'data' => [
                'reference' => is_string($data['tx_ref'] ?? null) ? $data['tx_ref'] : $reference,
                'status' => $status === 'successful' ? 'success' : $status,
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function refundPayment(string $transactionReference, int $amountKobo): array
    {
        $response = $this->connector->refundTransaction($transactionReference, $amountKobo);

        if (($response['status'] ?? '') !== 'success') {
            return [
                'status' => false,
                'message' => (string) ($response['message'] ?? 'Flutterwave refund failed.'),
            ];
        }

        $data = is_array($response['data'] ?? null) ? $response['data'] : [];

        return [
            'status' => true,
            'message' => (string) ($response['message'] ?? 'Refund processed'),
            'data' => [
                'reference' => is_string($data['id'] ?? null) ? $data['id'] : '',
                'transaction' => [
                    'reference' => is_string($data['tx_ref'] ?? null) ? $data['tx_ref'] : $transactionReference,
                ],
                'amount' => $amountKobo,
                'status' => is_string($data['status'] ?? null) ? $data['status'] : 'processed',
            ],
        ];
    }
}
