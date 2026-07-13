<?php

declare(strict_types=1);

namespace Connectors\Flutterwave\Tests\Unit;

use Connectors\Flutterwave\FlutterwaveConnector;
use Connectors\Flutterwave\FlutterwaveServiceProvider;
use Orchestra\Testbench\TestCase;

final class FlutterwaveConnectorTest extends TestCase
{
    /**
     * @param  \Illuminate\Foundation\Application  $app
     * @return array<int, class-string>
     */
    protected function getPackageProviders($app): array
    {
        return [FlutterwaveServiceProvider::class];
    }

    public function test_initialize_transaction_returns_stub_response(): void
    {
        $connector = new FlutterwaveConnector;

        $response = $connector->initializeTransaction([
            'email' => 'buyer@example.com',
            'amount' => 500_000,
            'reference' => 'order_ref_123',
        ]);

        $this->assertSame('success', $response['status']);
        $this->assertSame('order_ref_123', $response['data']['tx_ref']);
        $this->assertStringStartsWith('https://checkout.flutterwave.com/', $response['data']['link']);
    }

    public function test_verify_transaction_returns_stub_response(): void
    {
        $connector = new FlutterwaveConnector;

        $response = $connector->verifyTransaction('order_ref_123');

        $this->assertSame('success', $response['status']);
        $this->assertSame('order_ref_123', $response['data']['tx_ref']);
        $this->assertSame('successful', $response['data']['status']);
    }

    public function test_verify_webhook_signature_accepts_charge_completed_in_stub_mode(): void
    {
        $connector = new FlutterwaveConnector;
        $payload = json_encode([
            'event' => 'charge.completed',
            'data' => [
                'tx_ref' => 'ref_123',
                'amount' => 5000,
                'status' => 'successful',
                'currency' => 'NGN',
            ],
        ], JSON_THROW_ON_ERROR);

        $this->assertTrue($connector->verifyWebhookSignature($payload, ''));
    }

    public function test_verify_webhook_signature_rejects_invalid_hash_when_secret_configured(): void
    {
        config([
            'flutterwave.secret_hash' => 'expected-hash',
        ]);

        $connector = new FlutterwaveConnector;
        $payload = json_encode([
            'event' => 'charge.completed',
            'data' => [
                'tx_ref' => 'ref_123',
                'amount' => 5000,
                'status' => 'successful',
            ],
        ], JSON_THROW_ON_ERROR);

        $this->assertFalse($connector->verifyWebhookSignature($payload, 'invalid-hash'));
    }

    public function test_verify_webhook_signature_accepts_valid_hash_when_secret_configured(): void
    {
        config([
            'flutterwave.secret_hash' => 'expected-hash',
        ]);

        $connector = new FlutterwaveConnector;
        $payload = json_encode([
            'event' => 'charge.completed',
            'data' => [
                'tx_ref' => 'ref_123',
                'amount' => 5000,
                'status' => 'successful',
            ],
        ], JSON_THROW_ON_ERROR);

        $this->assertTrue($connector->verifyWebhookSignature($payload, 'expected-hash'));
    }

    public function test_refund_transaction_returns_stub_response(): void
    {
        $connector = new FlutterwaveConnector;

        $response = $connector->refundTransaction('order_ref_123', 250_000);

        $this->assertSame('success', $response['status']);
        $this->assertSame(2500.0, $response['data']['amount']);
        $this->assertSame('completed', $response['data']['status']);
    }

    public function test_handle_webhook_parses_event_fields(): void
    {
        $connector = new FlutterwaveConnector;

        $parsed = $connector->handleWebhook([
            'event' => 'charge.completed',
            'data' => [
                'tx_ref' => 'ref_456',
                'amount' => 7500,
                'status' => 'successful',
                'currency' => 'NGN',
                'id' => 99,
            ],
        ]);

        $this->assertSame([
            'event' => 'charge.completed',
            'reference' => 'ref_456',
            'amount' => 750_000,
            'status' => 'successful',
            'provider_case_id' => '99',
            'currency' => 'NGN',
        ], $parsed);
    }

    public function test_connector_is_registered_as_singleton(): void
    {
        $first = $this->app->make(\Connectors\Flutterwave\FlutterwaveConnectorInterface::class);
        $second = $this->app->make(\Connectors\Flutterwave\FlutterwaveConnectorInterface::class);

        $this->assertSame($first, $second);
    }
}
