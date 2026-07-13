<?php

declare(strict_types=1);

namespace Platform\FinancialServices\Tests\Feature\FinancialServices;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Str;
use Modules\Commerce\Cart\Models\Cart;
use Modules\Commerce\Cart\Models\CartItem;
use Modules\Commerce\Catalog\Models\Product;
use Modules\Commerce\Checkout\Models\CheckoutSession;
use Modules\Commerce\Orders\Models\Order;
use Platform\Tenancy\Models\Tenant;
use Tests\Feature\PlatformTestCase;

final class PaystackWebhookTest extends PlatformTestCase
{
    public function test_valid_charge_success_webhook_completes_checkout(): void
    {
        $tenant = $this->createTenant();
        $reference = $this->initializeCheckoutPayment($tenant);

        $response = $this->postJson(
            '/api/v1/webhooks/paystack',
            $this->paystackWebhookPayload($reference, 3_000_000),
        );

        $response->assertOk()
            ->assertJson([
                'received' => true,
            ]);

        $this->assertDatabaseHas('checkout_sessions', [
            'tenant_id' => $tenant->id,
            'paystack_reference' => $reference,
            'status' => CheckoutSession::STATUS_COMPLETED,
        ]);
    }

    public function test_invalid_signature_returns_401(): void
    {
        Config::set('paystack.secret_key', 'sk_test_secret_key');

        $payload = $this->paystackWebhookPayload('ref_invalid_sig', 1_000_000);

        $response = $this->postJson(
            '/api/v1/webhooks/paystack',
            $payload,
            [
                'X-Paystack-Signature' => 'invalid-signature',
            ],
        );

        $response->assertUnauthorized()
            ->assertJson([
                'message' => 'Invalid signature.',
            ]);
    }

    public function test_duplicate_webhook_is_idempotent(): void
    {
        $tenant = $this->createTenant();
        $reference = $this->initializeCheckoutPayment($tenant);
        $payload = $this->paystackWebhookPayload($reference, 3_000_000);

        $first = $this->postJson('/api/v1/webhooks/paystack', $payload);
        $second = $this->postJson('/api/v1/webhooks/paystack', $payload);

        $first->assertOk()->assertJson(['received' => true]);
        $second->assertOk()->assertJson(['received' => true]);

        $this->assertDatabaseCount('webhook_events', 1);

        $session = CheckoutSession::query()
            ->where('paystack_reference', $reference)
            ->firstOrFail();

        $this->assertSame(CheckoutSession::STATUS_COMPLETED, $session->status);
        $this->assertSame(
            1,
            Order::query()
                ->where('checkout_session_id', $session->id)
                ->count(),
        );
    }

    public function test_webhook_rejects_amount_mismatch(): void
    {
        $tenant = $this->createTenant();
        $reference = $this->initializeCheckoutPayment($tenant);

        $response = $this->postJson(
            '/api/v1/webhooks/paystack',
            $this->paystackWebhookPayload($reference, 1_000_000),
        );

        $response->assertUnprocessable()
            ->assertJsonFragment([
                'message' => 'Webhook payment amount does not match checkout total.',
            ]);

        $this->assertDatabaseHas('checkout_sessions', [
            'tenant_id' => $tenant->id,
            'paystack_reference' => $reference,
            'status' => CheckoutSession::STATUS_PENDING,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function paystackWebhookPayload(string $reference, int $amount): array
    {
        return [
            'event' => 'charge.success',
            'data' => [
                'reference' => $reference,
                'amount' => $amount,
                'status' => 'success',
            ],
        ];
    }

    private function initializeCheckoutPayment(Tenant $tenant): string
    {
        $cart = $this->createCartWithItems($tenant, 3_000_000);

        $checkout = $this->postJson('/api/v1/commerce/checkout/sessions', [
            'cart_id' => $cart->id,
        ], $this->tenantMoneyHeaders($tenant->id));

        $sessionId = $checkout->json('data.session_id');

        $initialize = $this->postJson('/api/v1/platform/financial-services/payments/initialize', [
            'checkout_session_id' => $sessionId,
            'email' => 'buyer@example.com',
        ], $this->tenantMoneyHeaders($tenant->id));

        return (string) $initialize->json('data.reference');
    }

    private function createTenant(string $prefix = 'webhook'): Tenant
    {
        return Tenant::query()->create([
            'slug' => $prefix.'-'.Str::random(8),
            'name' => ucfirst($prefix).' Tenant',
            'status' => 'active',
            'country' => 'NG',
        ]);
    }

    private function createCartWithItems(Tenant $tenant, int $lineTotalKobo): Cart
    {
        $product = Product::query()->create([
            'tenant_id' => $tenant->id,
            'name' => 'Webhook Product',
            'slug' => 'webhook-product-'.Str::random(6),
            'price_kobo' => $lineTotalKobo,
            'status' => 'published',
            'inventory_qty' => 5,
        ]);

        $cart = Cart::query()->create([
            'tenant_id' => $tenant->id,
            'session_id' => (string) Str::uuid(),
            'currency' => 'NGN',
        ]);

        CartItem::query()->create([
            'cart_id' => $cart->id,
            'product_id' => $product->id,
            'quantity' => 1,
            'unit_price_kobo' => $lineTotalKobo,
            'line_total_kobo' => $lineTotalKobo,
        ]);

        return $cart;
    }
}
