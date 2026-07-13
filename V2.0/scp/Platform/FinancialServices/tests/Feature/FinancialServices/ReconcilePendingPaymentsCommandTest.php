<?php

declare(strict_types=1);

namespace Platform\FinancialServices\Tests\Feature\FinancialServices;

use Illuminate\Support\Str;
use Modules\Commerce\Cart\Models\Cart;
use Modules\Commerce\Cart\Models\CartItem;
use Modules\Commerce\Catalog\Models\Product;
use Modules\Commerce\Checkout\Models\CheckoutSession;
use Platform\Tenancy\Models\Tenant;
use Tests\Feature\PlatformTestCase;

final class ReconcilePendingPaymentsCommandTest extends PlatformTestCase
{
    public function test_command_reconciles_stale_pending_checkout_session(): void
    {
        $tenant = $this->createTenant();
        $session = $this->createPendingCheckoutSession($tenant, 'reconcile_ref_'.Str::random(6));

        $session->forceFill([
            'updated_at' => now()->subMinutes(30),
        ])->save();

        $this->artisan('scp:reconcile-pending-payments', ['--minutes' => 15])
            ->assertSuccessful();

        $this->assertDatabaseHas('checkout_sessions', [
            'id' => $session->id,
            'status' => CheckoutSession::STATUS_COMPLETED,
        ]);
    }

    public function test_command_ignores_recent_pending_sessions(): void
    {
        $tenant = $this->createTenant();
        $session = $this->createPendingCheckoutSession($tenant, 'recent_ref_'.Str::random(6));

        $this->artisan('scp:reconcile-pending-payments', ['--minutes' => 15])
            ->assertSuccessful();

        $this->assertDatabaseHas('checkout_sessions', [
            'id' => $session->id,
            'status' => CheckoutSession::STATUS_PENDING,
        ]);
    }

    private function createTenant(): Tenant
    {
        return Tenant::query()->create([
            'slug' => 'reconcile-'.Str::random(6),
            'name' => 'Reconcile Tenant',
            'status' => 'active',
            'country' => 'NG',
        ]);
    }

    private function createPendingCheckoutSession(Tenant $tenant, string $reference): CheckoutSession
    {
        $product = Product::query()->create([
            'tenant_id' => $tenant->id,
            'name' => 'Reconcile Product',
            'slug' => 'reconcile-product-'.Str::random(4),
            'price_kobo' => 3_000_000,
            'status' => 'published',
            'inventory_qty' => 2,
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
            'unit_price_kobo' => 3_000_000,
            'line_total_kobo' => 3_000_000,
        ]);

        return CheckoutSession::query()->create([
            'tenant_id' => $tenant->id,
            'cart_id' => $cart->id,
            'status' => CheckoutSession::STATUS_PENDING,
            'total_kobo' => 3_000_000,
            'paystack_reference' => $reference,
        ]);
    }
}
