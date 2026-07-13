<?php

declare(strict_types=1);

namespace Connectors\Paystack\Tests\Unit;

use Connectors\Paystack\PaystackConnector;
use Orchestra\Testbench\TestCase;
use Connectors\Paystack\PaystackServiceProvider;

final class PaystackConnectorTest extends TestCase
{
    /**
     * @param  \Illuminate\Foundation\Application  $app
     * @return array<int, class-string>
     */
    protected function getPackageProviders($app): array
    {
        return [PaystackServiceProvider::class];
    }

    public function test_initialize_transaction_returns_stub_response(): void
    {
        $connector = new PaystackConnector;

        $response = $connector->initializeTransaction([
            'email' => 'buyer@example.com',
            'amount' => 500_000,
            'reference' => 'order_ref_123',
        ]);

        $this->assertTrue($response['status']);
        $this->assertSame('order_ref_123', $response['data']['reference']);
        $this->assertStringStartsWith('https://checkout.paystack.com/', $response['data']['authorization_url']);
    }

    public function test_verify_transaction_returns_stub_response(): void
    {
        $connector = new PaystackConnector;

        $response = $connector->verifyTransaction('order_ref_123');

        $this->assertTrue($response['status']);
        $this->assertSame('order_ref_123', $response['data']['reference']);
        $this->assertSame('success', $response['data']['status']);
    }

    public function test_verify_webhook_signature_accepts_charge_success_in_stub_mode(): void
    {
        $connector = new PaystackConnector;
        $payload = json_encode([
            'event' => 'charge.success',
            'data' => [
                'reference' => 'ref_123',
                'amount' => 500_000,
                'status' => 'success',
            ],
        ], JSON_THROW_ON_ERROR);

        $this->assertTrue($connector->verifyWebhookSignature($payload, ''));
    }

    public function test_verify_webhook_signature_rejects_invalid_hmac_when_secret_configured(): void
    {
        config([
            'paystack.secret_key' => 'sk_test_secret',
        ]);

        $connector = new PaystackConnector;
        $payload = json_encode([
            'event' => 'charge.success',
            'data' => [
                'reference' => 'ref_123',
                'amount' => 500_000,
                'status' => 'success',
            ],
        ], JSON_THROW_ON_ERROR);

        $this->assertFalse($connector->verifyWebhookSignature($payload, 'invalid-signature'));
    }

    public function test_verify_webhook_signature_accepts_valid_hmac_when_secret_configured(): void
    {
        $secret = 'sk_test_secret';
        config([
            'paystack.secret_key' => $secret,
        ]);

        $connector = new PaystackConnector;
        $payload = json_encode([
            'event' => 'charge.success',
            'data' => [
                'reference' => 'ref_123',
                'amount' => 500_000,
                'status' => 'success',
            ],
        ], JSON_THROW_ON_ERROR);
        $signature = hash_hmac('sha512', $payload, $secret);

        $this->assertTrue($connector->verifyWebhookSignature($payload, $signature));
    }

    public function test_refund_transaction_returns_stub_response(): void
    {
        $connector = new PaystackConnector;

        $response = $connector->refundTransaction('order_ref_123', 250_000);

        $this->assertTrue($response['status']);
        $this->assertSame(250_000, $response['data']['amount']);
        $this->assertSame('processed', $response['data']['status']);
    }

    public function test_initialize_transaction_calls_paystack_http_outside_testing(): void
    {
        $this->app->detectEnvironment(static fn (): string => 'local');
        config(['paystack.secret_key' => 'sk_test_live']);

        \Illuminate\Support\Facades\Http::fake([
            'api.paystack.co/transaction/initialize' => \Illuminate\Support\Facades\Http::response([
                'status' => true,
                'message' => 'Authorization URL created',
                'data' => [
                    'authorization_url' => 'https://checkout.paystack.com/live',
                    'access_code' => 'live_access',
                    'reference' => 'live_ref_123',
                ],
            ], 200),
        ]);

        $connector = new PaystackConnector;
        $response = $connector->initializeTransaction([
            'email' => 'buyer@example.com',
            'amount' => 500_000,
            'reference' => 'live_ref_123',
        ]);

        $this->assertTrue($response['status']);
        $this->assertSame('live_ref_123', $response['data']['reference']);

        \Illuminate\Support\Facades\Http::assertSent(function (\Illuminate\Http\Client\Request $request): bool {
            return $request->url() === 'https://api.paystack.co/transaction/initialize'
                && $request->method() === 'POST';
        });
    }

    public function test_verify_transaction_calls_paystack_http_outside_testing(): void
    {
        $this->app->detectEnvironment(static fn (): string => 'local');
        config(['paystack.secret_key' => 'sk_test_live']);

        \Illuminate\Support\Facades\Http::fake([
            'api.paystack.co/transaction/verify/*' => \Illuminate\Support\Facades\Http::response([
                'status' => true,
                'message' => 'Verification successful',
                'data' => [
                    'reference' => 'live_ref_456',
                    'status' => 'success',
                ],
            ], 200),
        ]);

        $connector = new PaystackConnector;
        $response = $connector->verifyTransaction('live_ref_456');

        $this->assertTrue($response['status']);
        $this->assertSame('success', $response['data']['status']);
    }

    public function test_handle_webhook_parses_event_fields(): void
    {
        $connector = new PaystackConnector;

        $parsed = $connector->handleWebhook([
            'event' => 'charge.success',
            'data' => [
                'reference' => 'ref_456',
                'amount' => 750_000,
                'status' => 'success',
            ],
        ]);

        $this->assertSame([
            'event' => 'charge.success',
            'reference' => 'ref_456',
            'amount' => 750_000,
            'status' => 'success',
            'provider_case_id' => '',
            'currency' => 'NGN',
        ], $parsed);
    }

    public function test_connector_is_registered_as_singleton(): void
    {
        $first = $this->app->make(\Connectors\Paystack\PaystackConnectorInterface::class);
        $second = $this->app->make(\Connectors\Paystack\PaystackConnectorInterface::class);

        $this->assertSame($first, $second);
    }
}
