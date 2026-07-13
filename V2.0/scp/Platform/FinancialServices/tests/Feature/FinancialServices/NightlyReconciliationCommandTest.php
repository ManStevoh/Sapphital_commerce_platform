<?php

declare(strict_types=1);

namespace Platform\FinancialServices\Tests\Feature\FinancialServices;

use Illuminate\Support\Str;
use Modules\Commerce\Cart\Models\Cart;
use Modules\Commerce\Catalog\Models\Product;
use Modules\Commerce\Checkout\Models\CheckoutSession;
use Modules\Commerce\Orders\Models\Order;
use Platform\Tenancy\Models\Tenant;
use Tests\Feature\PlatformTestCase;

final class NightlyReconciliationCommandTest extends PlatformTestCase
{
    public function test_nightly_command_reports_clean_state(): void
    {
        $this->artisan('scp:reconcile-nightly', ['--minutes' => 15])
            ->assertSuccessful()
            ->expectsOutputToContain('Nightly reconciliation complete.');
    }

    public function test_nightly_command_detects_completed_session_without_order(): void
    {
        $tenant = $this->createTenant();
        $cart = Cart::query()->create([
            'tenant_id' => $tenant->id,
            'session_id' => (string) Str::uuid(),
            'currency' => 'NGN',
        ]);

        CheckoutSession::query()->create([
            'tenant_id' => $tenant->id,
            'cart_id' => $cart->id,
            'status' => CheckoutSession::STATUS_COMPLETED,
            'total_kobo' => 100_000,
        ]);

        $this->artisan('scp:reconcile-nightly')
            ->assertSuccessful()
            ->expectsOutputToContain('Completed checkout sessions without order: 1');
    }

    public function test_nightly_command_detects_paid_order_with_incomplete_session(): void
    {
        $tenant = $this->createTenant();
        $cart = Cart::query()->create([
            'tenant_id' => $tenant->id,
            'session_id' => (string) Str::uuid(),
            'currency' => 'NGN',
        ]);

        $session = CheckoutSession::query()->create([
            'tenant_id' => $tenant->id,
            'cart_id' => $cart->id,
            'status' => CheckoutSession::STATUS_PENDING,
            'total_kobo' => 200_000,
        ]);

        Order::query()->create([
            'tenant_id' => $tenant->id,
            'checkout_session_id' => $session->id,
            'order_number' => 'ORD-REC-'.Str::upper(Str::random(4)),
            'status' => Order::STATUS_PAID,
            'currency' => 'NGN',
            'subtotal_kobo' => 200_000,
            'total_kobo' => 200_000,
            'paystack_reference' => 'pay_ref_123',
        ]);

        $this->artisan('scp:reconcile-nightly')
            ->assertSuccessful()
            ->expectsOutputToContain('Paid orders with incomplete checkout session: 1');
    }

    private function createTenant(): Tenant
    {
        return Tenant::query()->create([
            'slug' => 'nightly-'.Str::random(6),
            'name' => 'Nightly Tenant',
            'status' => 'active',
            'country' => 'NG',
        ]);
    }
}
