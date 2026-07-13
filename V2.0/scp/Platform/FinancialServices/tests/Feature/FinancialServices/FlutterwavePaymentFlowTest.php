<?php

declare(strict_types=1);

namespace Platform\FinancialServices\Tests\Feature\FinancialServices;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Str;
use Modules\Commerce\Cart\Models\Cart;
use Modules\Commerce\Cart\Models\CartItem;
use Modules\Commerce\Catalog\Models\Product;
use Modules\Commerce\Checkout\Models\CheckoutSession;
use Platform\Tenancy\Models\Tenant;
use Tests\Feature\PlatformTestCase;

final class FlutterwavePaymentFlowTest extends PlatformTestCase
{
    public function test_checkout_can_initialize_with_flutterwave_provider(): void
    {
        $tenant = $this->createTenant();
        $session = $this->createCheckoutSession($tenant);

        $response = $this->postJson(
            '/api/v1/platform/financial-services/payments/initialize',
            [
                'checkout_session_id' => $session->id,
                'email' => 'buyer@example.com',
                'provider' => 'flutterwave',
            ],
            $this->tenantMoneyHeaders($tenant->id),
        );

        $response->assertOk()
            ->assertJsonPath('data.authorization_url', 'https://checkout.flutterwave.com/stub')
            ->assertJsonStructure(['data' => ['reference']]);
    }

    public function test_charge_completed_webhook_completes_checkout(): void
    {
        $tenant = $this->createTenant();
        $session = $this->createCheckoutSession($tenant);

        $initialize = $this->postJson(
            '/api/v1/platform/financial-services/payments/initialize',
            [
                'checkout_session_id' => $session->id,
                'email' => 'buyer@example.com',
                'provider' => 'flutterwave',
            ],
            $this->tenantMoneyHeaders($tenant->id),
        );

        $reference = (string) $initialize->json('data.reference');

        $response = $this->postJson(
            '/api/v1/webhooks/flutterwave',
            $this->flutterwaveWebhookPayload($reference, 3_000_000),
        );

        $response->assertOk()
            ->assertJson(['received' => true]);

        $this->assertDatabaseHas('checkout_sessions', [
            'id' => $session->id,
            'paystack_reference' => $reference,
            'status' => CheckoutSession::STATUS_COMPLETED,
        ]);
    }

    public function test_invalid_flutterwave_signature_returns_401(): void
    {
        Config::set('flutterwave.secret_hash', 'expected-hash');

        $response = $this->postJson(
            '/api/v1/webhooks/flutterwave',
            $this->flutterwaveWebhookPayload('ref_invalid_sig', 1_000_000),
            ['verif-hash' => 'invalid-hash'],
        );

        $response->assertUnauthorized()
            ->assertJson(['message' => 'Invalid signature.']);
    }

    /**
     * @return array{event: string, data: array<string, mixed>}
     */
    private function flutterwaveWebhookPayload(string $reference, int $amountKobo): array
    {
        return [
            'event' => 'charge.completed',
            'data' => [
                'tx_ref' => $reference,
                'amount' => $amountKobo / 100,
                'status' => 'successful',
                'currency' => 'NGN',
                'id' => 12345,
            ],
        ];
    }

    private function createTenant(): Tenant
    {
        return Tenant::query()->create([
            'slug' => 'fw-'.Str::random(6),
            'name' => 'Flutterwave Tenant',
            'status' => 'active',
            'country' => 'NG',
        ]);
    }

    private function createCheckoutSession(Tenant $tenant): CheckoutSession
    {
        $product = Product::query()->create([
            'tenant_id' => $tenant->id,
            'name' => 'FW Product',
            'slug' => 'fw-product-'.Str::random(4),
            'price_kobo' => 3_000_000,
            'status' => 'published',
            'inventory_qty' => 5,
        ]);

        $cart = Cart::query()->create([
            'tenant_id' => $tenant->id,
            'session_id' => Str::uuid()->toString(),
            'currency' => 'NGN',
            'subtotal_kobo' => 3_000_000,
            'total_kobo' => 3_000_000,
        ]);

        CartItem::query()->create([
            'cart_id' => $cart->id,
            'product_id' => $product->id,
            'quantity' => 1,
            'unit_price_kobo' => 3_000_000,
            'line_total_kobo' => 3_000_000,
        ]);

        return CheckoutSession::query()->create([
            'tenant_id' => $tenant->id,
            'cart_id' => $cart->id,
            'status' => CheckoutSession::STATUS_PENDING,
            'currency' => 'NGN',
            'subtotal_kobo' => 3_000_000,
            'total_kobo' => 3_000_000,
            'customer_email' => 'buyer@example.com',
        ]);
    }
}
