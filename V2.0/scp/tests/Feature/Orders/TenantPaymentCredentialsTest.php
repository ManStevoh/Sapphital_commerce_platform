<?php

declare(strict_types=1);

namespace Tests\Feature\Orders;

use Illuminate\Support\Str;
use Modules\Commerce\Cart\Models\Cart;
use Modules\Commerce\Checkout\Models\CheckoutSession;
use Platform\Billing\Enums\SubscriptionStatus;
use Platform\Billing\Models\Plan;
use Platform\Billing\Models\Subscription;
use Platform\Identity\Enums\MerchantUserRole;
use Platform\FinancialServices\Services\WebhookSignatureResolver;
use Platform\Secrets\Contracts\SecretVaultInterface;
use Platform\Tenancy\Models\Tenant;
use Tests\Feature\PlatformTestCase;

final class TenantPaymentCredentialsTest extends PlatformTestCase
{
    private string $tempDirectory;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tempDirectory = sys_get_temp_dir().DIRECTORY_SEPARATOR.'scp-psp-creds-'.uniqid('', true);
        mkdir($this->tempDirectory, 0700, true);

        config([
            'secrets.driver' => 'file',
            'secrets.paths.default' => $this->tempDirectory,
        ]);
    }

    protected function tearDown(): void
    {
        $this->removeDirectory($this->tempDirectory);

        parent::tearDown();
    }

    public function test_finance_user_can_store_and_view_masked_paystack_credentials(): void
    {
        $tenant = $this->createTenant();
        $headers = $this->financeHeaders($tenant);

        $this->getJson('/api/v1/commerce/storefront/settings/payments/credentials', $headers)
            ->assertOk()
            ->assertJsonPath('data.paystack.configured', false);

        $response = $this->putJson('/api/v1/commerce/storefront/settings/payments/credentials', [
            'provider' => 'paystack',
            'secret_key' => 'sk_test_merchant_secret_key',
        ], $headers);

        $response->assertOk()
            ->assertJsonPath('data.paystack.configured', true);

        $masked = (string) $response->json('data.paystack.masked_secret_key');
        $this->assertStringStartsWith('sk_t', $masked);
        $this->assertStringEndsWith('_key', $masked);
        $this->assertStringNotContainsString('merchant', $masked);

        $vault = $this->app->make(SecretVaultInterface::class);
        $this->assertSame(
            'sk_test_merchant_secret_key',
            $vault->get("tenant.{$tenant->id}.paystack.secret_key"),
        );
    }

    public function test_finance_user_can_store_flutterwave_secret_and_webhook_hash(): void
    {
        $tenant = $this->createTenant();
        $headers = $this->financeHeaders($tenant);

        $this->putJson('/api/v1/commerce/storefront/settings/payments/credentials', [
            'provider' => 'flutterwave',
            'secret_key' => 'FLWSECK_TEST_MERCHANT',
            'secret_hash' => 'merchant-webhook-hash',
        ], $headers)->assertOk()
            ->assertJsonPath('data.flutterwave.configured', true)
            ->assertJsonPath('data.flutterwave.webhook_hash_configured', true);

        $vault = $this->app->make(SecretVaultInterface::class);
        $this->assertSame(
            'FLWSECK_TEST_MERCHANT',
            $vault->get("tenant.{$tenant->id}.flutterwave.secret_key"),
        );
        $this->assertSame(
            'merchant-webhook-hash',
            $vault->get("tenant.{$tenant->id}.flutterwave.secret_hash"),
        );
    }

    public function test_webhook_signature_resolver_uses_tenant_paystack_secret(): void
    {
        $tenant = $this->createTenant();
        $tenantSecret = 'sk_test_tenant_webhook_secret';
        $vault = $this->app->make(SecretVaultInterface::class);
        $vault->set("tenant.{$tenant->id}.paystack.secret_key", $tenantSecret);

        $reference = 'tenant_ref_'.Str::random(8);
        $this->createCheckoutSession($tenant, $reference);

        $payload = json_encode([
            'event' => 'charge.success',
            'data' => [
                'reference' => $reference,
                'amount' => 5_000_00,
                'status' => 'success',
            ],
        ], JSON_THROW_ON_ERROR);

        $signature = hash_hmac('sha512', $payload, $tenantSecret);
        $resolver = $this->app->make(WebhookSignatureResolver::class);

        $this->assertTrue($resolver->verifyPaystack($payload, $signature));
        $this->assertFalse($resolver->verifyPaystack($payload, 'invalid-signature'));
    }

    public function test_paystack_webhook_rejects_invalid_tenant_signature(): void
    {
        $tenant = $this->createTenant();
        $vault = $this->app->make(SecretVaultInterface::class);
        $vault->set("tenant.{$tenant->id}.paystack.secret_key", 'sk_test_tenant_webhook_secret');

        $reference = 'tenant_ref_'.Str::random(8);
        $this->createCheckoutSession($tenant, $reference);

        $payload = [
            'event' => 'charge.success',
            'data' => [
                'reference' => $reference,
                'amount' => 5_000_00,
                'status' => 'success',
            ],
        ];

        $this->postJson('/api/v1/webhooks/paystack', $payload, [
            'X-Paystack-Signature' => 'invalid-signature',
        ])->assertUnauthorized();
    }

    /**
     * @param  array<string, mixed>  $settings
     */
    private function createTenant(array $settings = []): Tenant
    {
        return Tenant::query()->create([
            'slug' => 'psp-creds-'.Str::random(6),
            'name' => 'PSP Credentials Tenant',
            'status' => 'active',
            'country' => 'NG',
            'settings' => array_merge([
                'currency' => 'NGN',
                'timezone' => 'Africa/Lagos',
                'payment_provider' => 'paystack',
            ], $settings),
        ]);
    }

    /**
     * @return array<string, string>
     */
    private function financeHeaders(Tenant $tenant): array
    {
        $merchant = $this->createMerchantForTenant(
            $tenant,
            'psp-creds@test.com',
            'password12345',
            MerchantUserRole::Finance,
        );
        $this->createActiveSubscription($tenant->id);

        return $this->merchantAuthHeaders($tenant->id, $this->login($merchant->email));
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

    private function createCheckoutSession(Tenant $tenant, string $reference): CheckoutSession
    {
        $cart = Cart::query()->create([
            'tenant_id' => $tenant->id,
            'session_id' => Str::uuid()->toString(),
            'currency' => 'NGN',
            'subtotal_kobo' => 5_000_00,
            'total_kobo' => 5_000_00,
        ]);

        return CheckoutSession::query()->create([
            'tenant_id' => $tenant->id,
            'cart_id' => $cart->id,
            'status' => CheckoutSession::STATUS_PENDING,
            'currency' => 'NGN',
            'subtotal_kobo' => 5_000_00,
            'total_kobo' => 5_000_00,
            'customer_email' => 'buyer@example.com',
            'paystack_reference' => $reference,
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

    private function removeDirectory(string $directory): void
    {
        if (! is_dir($directory)) {
            return;
        }

        $items = scandir($directory);

        if ($items === false) {
            return;
        }

        foreach ($items as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }

            $path = $directory.DIRECTORY_SEPARATOR.$item;

            if (is_dir($path)) {
                $this->removeDirectory($path);
            } else {
                unlink($path);
            }
        }

        rmdir($directory);
    }
}
