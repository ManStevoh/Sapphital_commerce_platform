<?php

declare(strict_types=1);

namespace Tests\Feature\Checkout;

use Illuminate\Support\Str;
use Modules\Commerce\Cart\Models\Cart;
use Modules\Commerce\Cart\Models\CartItem;
use Modules\Commerce\Catalog\Models\Product;
use Modules\Commerce\Checkout\Enums\GiftCardStatus;
use Modules\Commerce\Checkout\Models\CheckoutSession;
use Modules\Commerce\Checkout\Models\GiftCard;
use Modules\Commerce\Orders\Services\OrderService;
use Platform\Tenancy\Models\Tenant;
use Tests\Feature\PlatformTestCase;

final class GiftCardEndpointTest extends PlatformTestCase
{
    public function test_merchant_can_issue_preset_gift_card(): void
    {
        $tenant = $this->createTenant();
        $headers = $this->merchantHeaders($tenant);

        $response = $this->postJson('/api/v1/commerce/gift-cards', [
            'denomination_kobo' => 500_000,
            'recipient_email' => 'gift@example.com',
        ], $headers);

        $response->assertCreated()
            ->assertJsonPath('data.balance_kobo', 500_000)
            ->assertJsonPath('data.initial_balance_kobo', 500_000)
            ->assertJsonPath('data.status', 'active')
            ->assertJsonPath('data.currency', 'NGN');

        $this->assertNotEmpty($response->json('data.code'));
        $this->assertDatabaseHas('gift_cards', [
            'tenant_id' => $tenant->id,
            'balance_kobo' => 500_000,
            'status' => 'active',
        ]);
    }

    public function test_issue_rejects_non_preset_denomination(): void
    {
        $tenant = $this->createTenant();

        $this->postJson('/api/v1/commerce/gift-cards', [
            'denomination_kobo' => 123_000,
        ], $this->merchantHeaders($tenant))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['denomination_kobo']);
    }

    public function test_apply_gift_card_reduces_checkout_total_partially(): void
    {
        $tenant = $this->createTenant();
        $headers = $this->merchantHeaders($tenant);

        $issue = $this->postJson('/api/v1/commerce/gift-cards', [
            'denomination_kobo' => 500_000,
        ], $headers)->assertCreated();

        $code = (string) $issue->json('data.code');
        $session = $this->createPendingSession($tenant, 1_200_000);

        $apply = $this->postJson(
            "/api/v1/commerce/checkout/sessions/{$session->id}/gift-card",
            ['code' => $code],
            ['X-Tenant-ID' => $tenant->id],
        );

        $apply->assertOk()
            ->assertJsonPath('data.gift_card_applied_kobo', 500_000)
            ->assertJsonPath('data.total_kobo', 700_000);

        $this->assertSame(500_000, (int) $session->fresh()->gift_card_applied_kobo);
    }

    public function test_order_finalizes_redemption_and_partial_balance_remains(): void
    {
        $tenant = $this->createTenant();
        $headers = $this->merchantHeaders($tenant);

        $issue = $this->postJson('/api/v1/commerce/gift-cards', [
            'denomination_kobo' => 1_000_000,
        ], $headers)->assertCreated();

        $code = (string) $issue->json('data.code');
        $cardId = (string) $issue->json('data.id');
        $session = $this->createPendingSession($tenant, 400_000);

        $this->postJson(
            "/api/v1/commerce/checkout/sessions/{$session->id}/gift-card",
            ['code' => $code],
            ['X-Tenant-ID' => $tenant->id],
        )->assertOk()->assertJsonPath('data.total_kobo', 0);

        $order = app(OrderService::class)->createFromCheckoutSession($session->fresh());

        $card = GiftCard::query()->findOrFail($cardId);
        $this->assertSame(600_000, $card->balance_kobo);
        $this->assertSame(GiftCardStatus::Active, $card->status);
        $this->assertSame(0, (int) $order->total_kobo);

        $this->assertDatabaseHas('gift_card_transactions', [
            'gift_card_id' => $cardId,
            'order_id' => $order->id,
            'delta_kobo' => -400_000,
            'type' => 'redeem',
        ]);
    }

    public function test_expired_and_depleted_cards_are_rejected(): void
    {
        $tenant = $this->createTenant();
        $session = $this->createPendingSession($tenant, 500_000);

        $expired = GiftCard::query()->create([
            'tenant_id' => $tenant->id,
            'code' => 'GC-EXP-'.Str::upper(Str::random(4)),
            'initial_balance_kobo' => 500_000,
            'balance_kobo' => 500_000,
            'currency' => 'NGN',
            'status' => GiftCardStatus::Active,
            'expires_at' => now()->subDay(),
        ]);

        $this->postJson(
            "/api/v1/commerce/checkout/sessions/{$session->id}/gift-card",
            ['code' => $expired->code],
            ['X-Tenant-ID' => $tenant->id],
        )->assertUnprocessable();

        $this->assertSame(GiftCardStatus::Expired, $expired->fresh()->status);

        $depleted = GiftCard::query()->create([
            'tenant_id' => $tenant->id,
            'code' => 'GC-DEP-'.Str::upper(Str::random(4)),
            'initial_balance_kobo' => 500_000,
            'balance_kobo' => 0,
            'currency' => 'NGN',
            'status' => GiftCardStatus::Depleted,
            'expires_at' => now()->addYear(),
        ]);

        $this->postJson(
            "/api/v1/commerce/checkout/sessions/{$session->id}/gift-card",
            ['code' => $depleted->code],
            ['X-Tenant-ID' => $tenant->id],
        )->assertUnprocessable();
    }

    public function test_expire_gift_cards_command_marks_due_cards(): void
    {
        $tenant = $this->createTenant();

        $card = GiftCard::query()->create([
            'tenant_id' => $tenant->id,
            'code' => 'GC-DUE-'.Str::upper(Str::random(4)),
            'initial_balance_kobo' => 500_000,
            'balance_kobo' => 500_000,
            'currency' => 'NGN',
            'status' => GiftCardStatus::Active,
            'expires_at' => now()->subMinute(),
        ]);

        $this->artisan('checkout:expire-gift-cards')->assertSuccessful();

        $this->assertSame(GiftCardStatus::Expired, $card->fresh()->status);
    }

    public function test_full_gift_card_coverage_skips_psp_and_completes_order(): void
    {
        $tenant = $this->createTenant();
        $headers = $this->merchantHeaders($tenant);

        $issue = $this->postJson('/api/v1/commerce/gift-cards', [
            'denomination_kobo' => 2_500_000,
        ], $headers)->assertCreated();

        $code = (string) $issue->json('data.code');
        $session = $this->createPendingSession($tenant, 500_000);

        $this->postJson(
            "/api/v1/commerce/checkout/sessions/{$session->id}/gift-card",
            ['code' => $code],
            ['X-Tenant-ID' => $tenant->id],
        )->assertOk()->assertJsonPath('data.total_kobo', 0);

        $init = $this->postJson('/api/v1/platform/financial-services/payments/initialize', [
            'checkout_session_id' => $session->id,
            'email' => 'buyer@example.com',
        ], $this->tenantMoneyHeaders($tenant->id));

        $init->assertOk();

        $reference = (string) $init->json('data.reference');
        $authorizationUrl = (string) $init->json('data.authorization_url');

        $this->assertTrue(str_starts_with($reference, 'gc_'));
        $this->assertStringContainsString('/checkout/success', $authorizationUrl);

        $this->assertSame(CheckoutSession::STATUS_COMPLETED, $session->fresh()->status);

        $verify = $this->postJson('/api/v1/platform/financial-services/payments/verify', [
            'reference' => $reference,
        ], $this->tenantMoneyHeaders($tenant->id));

        $verify->assertOk()
            ->assertJsonPath('data.status', 'completed')
            ->assertJsonStructure(['data' => ['order_id']]);
    }

    /**
     * @return array<string, string>
     */
    private function merchantHeaders(Tenant $tenant): array
    {
        $merchant = $this->createMerchantForTenant($tenant, 'gift-'.Str::random(4).'@test.com');
        $token = $merchant->createToken('gift')->plainTextToken;

        return $this->merchantAuthHeaders($tenant->id, $token);
    }

    private function createPendingSession(Tenant $tenant, int $totalKobo): CheckoutSession
    {
        $product = Product::query()->create([
            'tenant_id' => $tenant->id,
            'name' => 'Gift Cart Product',
            'slug' => 'gift-cart-'.Str::random(6),
            'price_kobo' => $totalKobo,
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
            'unit_price_kobo' => $totalKobo,
            'line_total_kobo' => $totalKobo,
        ]);

        return CheckoutSession::query()->create([
            'tenant_id' => $tenant->id,
            'cart_id' => $cart->id,
            'status' => CheckoutSession::STATUS_PENDING,
            'total_kobo' => $totalKobo,
            'customer_email' => 'buyer@example.com',
        ]);
    }

    private function createTenant(string $prefix = 'gift'): Tenant
    {
        return Tenant::query()->create([
            'slug' => $prefix.'-'.Str::random(8),
            'name' => ucfirst($prefix).' Tenant',
            'status' => 'active',
            'country' => 'NG',
        ]);
    }
}
