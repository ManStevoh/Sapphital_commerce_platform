<?php

declare(strict_types=1);

namespace Tests\Feature\Orders;

use Illuminate\Support\Str;
use Modules\Commerce\Cart\Models\Cart;
use Modules\Commerce\Cart\Models\CartItem;
use Modules\Commerce\Catalog\Models\Product;
use Modules\Commerce\Checkout\Models\CheckoutSession;
use Platform\Billing\Enums\SubscriptionStatus;
use Platform\Billing\Models\Plan;
use Platform\Billing\Models\Subscription;
use Platform\Identity\Enums\MerchantUserRole;
use Platform\Tenancy\Models\Tenant;
use Tests\Feature\PlatformTestCase;

final class PaymentProviderSettingsTest extends PlatformTestCase
{
    public function test_storefront_exposes_checkout_payment_provider(): void
    {
        $tenant = $this->createTenant(['payment_provider' => 'flutterwave']);

        $this->getJson('/api/v1/commerce/storefront/checkout-settings', [
            'X-Tenant-ID' => $tenant->id,
        ])->assertOk()
            ->assertJsonPath('data.payment_provider', 'flutterwave')
            ->assertJsonPath('data.currency', 'NGN');
    }

    public function test_finance_user_can_update_payment_provider(): void
    {
        $tenant = $this->createTenant(['payment_provider' => 'paystack']);
        $merchant = $this->createMerchantForTenant(
            $tenant,
            'payments-settings@test.com',
            'password12345',
            MerchantUserRole::Finance,
        );
        $this->createActiveSubscription($tenant->id);
        $headers = $this->merchantAuthHeaders($tenant->id, $this->login($merchant->email));

        $this->getJson('/api/v1/commerce/storefront/settings/payments', $headers)
            ->assertOk()
            ->assertJsonPath('data.payment_provider', 'paystack');

        $this->putJson('/api/v1/commerce/storefront/settings/payments', [
            'payment_provider' => 'flutterwave',
        ], $headers)->assertOk()
            ->assertJsonPath('data.payment_provider', 'flutterwave');

        $tenant->refresh();
        $this->assertSame('flutterwave', $tenant->settings['payment_provider'] ?? null);
    }

    public function test_checkout_initialize_uses_tenant_payment_provider(): void
    {
        $tenant = $this->createTenant(['payment_provider' => 'flutterwave']);
        $session = $this->createCheckoutSession($tenant);

        $response = $this->postJson(
            '/api/v1/platform/financial-services/payments/initialize',
            [
                'checkout_session_id' => $session->id,
                'email' => 'buyer@example.com',
            ],
            $this->tenantMoneyHeaders($tenant->id),
        );

        $response->assertOk()
            ->assertJsonPath('data.authorization_url', 'https://checkout.flutterwave.com/stub');
    }

    /**
     * @param  array<string, mixed>  $settings
     */
    private function createTenant(array $settings = []): Tenant
    {
        return Tenant::query()->create([
            'slug' => 'psp-'.Str::random(6),
            'name' => 'PSP Tenant',
            'status' => 'active',
            'country' => 'NG',
            'settings' => array_merge([
                'currency' => 'NGN',
                'timezone' => 'Africa/Lagos',
                'payment_provider' => 'paystack',
            ], $settings),
        ]);
    }

    private function createCheckoutSession(Tenant $tenant): CheckoutSession
    {
        $product = Product::query()->create([
            'tenant_id' => $tenant->id,
            'name' => 'PSP Product',
            'slug' => 'psp-product-'.Str::random(4),
            'price_kobo' => 1_000_000,
            'status' => 'published',
            'inventory_qty' => 5,
        ]);

        $cart = Cart::query()->create([
            'tenant_id' => $tenant->id,
            'session_id' => Str::uuid()->toString(),
            'currency' => 'NGN',
            'subtotal_kobo' => 1_000_000,
            'total_kobo' => 1_000_000,
        ]);

        CartItem::query()->create([
            'cart_id' => $cart->id,
            'product_id' => $product->id,
            'quantity' => 1,
            'unit_price_kobo' => 1_000_000,
            'line_total_kobo' => 1_000_000,
        ]);

        return CheckoutSession::query()->create([
            'tenant_id' => $tenant->id,
            'cart_id' => $cart->id,
            'status' => CheckoutSession::STATUS_PENDING,
            'currency' => 'NGN',
            'subtotal_kobo' => 1_000_000,
            'total_kobo' => 1_000_000,
            'customer_email' => 'buyer@example.com',
        ]);
    }

    private function createActiveSubscription(string $tenantId): void
    {
        $plan = Plan::query()->where('slug', 'starter')->firstOrFail();

        Subscription::query()->create([
            'tenant_id' => $tenantId,
            'plan_id' => $plan->id,
            'status' => SubscriptionStatus::Active,
        ]);
    }

    private function login(string $email): string
    {
        $login = $this->postJson('/api/v1/auth/merchant/login', [
            'email' => $email,
            'password' => 'password12345',
        ]);

        return (string) $login->json('token');
    }
}
