<?php

declare(strict_types=1);

namespace Platform\FinancialServices\Tests\Feature\FinancialServices;

use Illuminate\Support\Str;
use Modules\Commerce\Orders\Models\Order;
use Modules\Commerce\Orders\Models\OrderItem;
use Platform\Billing\Enums\SubscriptionStatus;
use Platform\Billing\Models\Plan;
use Platform\Billing\Models\Subscription;
use Platform\FinancialServices\Models\Dispute;
use Platform\Tenancy\Models\Tenant;
use Tests\Feature\PlatformTestCase;

final class DisputeFlowTest extends PlatformTestCase
{
    public function test_charge_dispute_create_webhook_opens_dispute(): void
    {
        $tenant = $this->createTenant();
        $order = $this->createPaidOrder($tenant, 'pay_dispute_'.Str::random(6));

        $response = $this->postJson('/api/v1/webhooks/paystack', [
            'event' => 'charge.dispute.create',
            'data' => [
                'id' => 987654,
                'amount' => 500_000,
                'currency' => 'NGN',
                'status' => 'open',
                'transaction' => [
                    'reference' => $order->paystack_reference,
                ],
            ],
        ]);

        $response->assertOk()->assertJson(['received' => true]);

        $this->assertDatabaseHas('disputes', [
            'tenant_id' => $tenant->id,
            'order_id' => $order->id,
            'provider_case_id' => '987654',
            'status' => 'open',
        ]);
    }

    public function test_refund_blocked_when_open_dispute_exists(): void
    {
        $tenant = $this->createTenant();
        $merchant = $this->createMerchantForTenant($tenant, 'dispute@test.com', 'password12345');
        $this->createActiveSubscription($tenant->id);
        $order = $this->createPaidOrder($tenant, 'pay_block_'.Str::random(6));

        Dispute::query()->create([
            'tenant_id' => $tenant->id,
            'order_id' => $order->id,
            'type' => 'chargeback',
            'provider' => 'paystack',
            'status' => 'open',
            'provider_case_id' => 'case-'.Str::random(4),
            'amount_kobo' => 500_000,
            'currency' => 'NGN',
            'paystack_reference' => $order->paystack_reference,
        ]);

        $token = $this->loginMerchant($merchant->email, 'password12345');

        $this->postJson(
            "/api/v1/commerce/orders/{$order->id}/refund",
            ['amount_kobo' => 500_000],
            array_merge(
                $this->merchantAuthHeaders($tenant->id, $token),
                $this->idempotencyHeaders(),
            ),
        )->assertUnprocessable()
            ->assertJsonFragment([
                'message' => 'Refunds are blocked while an open dispute exists on this order.',
            ]);
    }

    public function test_merchant_can_list_and_resolve_disputes(): void
    {
        $tenant = $this->createTenant();
        $merchant = $this->createMerchantForTenant($tenant, 'resolve@test.com', 'password12345');
        $order = $this->createPaidOrder($tenant, 'pay_resolve_'.Str::random(6));

        $dispute = Dispute::query()->create([
            'tenant_id' => $tenant->id,
            'order_id' => $order->id,
            'type' => 'chargeback',
            'provider' => 'paystack',
            'status' => 'open',
            'provider_case_id' => 'case-'.Str::random(4),
            'amount_kobo' => 500_000,
            'currency' => 'NGN',
            'paystack_reference' => $order->paystack_reference,
        ]);

        $token = $this->loginMerchant($merchant->email, 'password12345');
        $headers = $this->merchantAuthHeaders($tenant->id, $token);

        $this->getJson('/api/v1/platform/financial-services/disputes', $headers)
            ->assertOk()
            ->assertJsonCount(1, 'data');

        $this->postJson(
            "/api/v1/platform/financial-services/disputes/{$dispute->id}/resolve",
            ['status' => 'won'],
            $headers,
        )->assertOk()
            ->assertJsonPath('data.status', 'won');
    }

    private function createTenant(): Tenant
    {
        return Tenant::query()->create([
            'slug' => 'dispute-'.Str::random(6),
            'name' => 'Dispute Tenant',
            'status' => 'active',
            'country' => 'NG',
        ]);
    }

    private function createPaidOrder(Tenant $tenant, string $reference): Order
    {
        $product = \Modules\Commerce\Catalog\Models\Product::query()->create([
            'tenant_id' => $tenant->id,
            'name' => 'Dispute Product',
            'slug' => 'dispute-product-'.Str::random(4),
            'price_kobo' => 500_000,
            'status' => 'published',
            'inventory_qty' => 1,
        ]);

        $order = Order::query()->create([
            'tenant_id' => $tenant->id,
            'checkout_session_id' => null,
            'order_number' => 'ORD-DSP-'.Str::upper(Str::random(4)),
            'status' => Order::STATUS_PAID,
            'currency' => 'NGN',
            'subtotal_kobo' => 500_000,
            'total_kobo' => 500_000,
            'paystack_reference' => $reference,
        ]);

        OrderItem::query()->create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'product_name' => $product->name,
            'quantity' => 1,
            'unit_price_kobo' => 500_000,
            'line_total_kobo' => 500_000,
        ]);

        return $order;
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

    private function loginMerchant(string $email, string $password): string
    {
        $login = $this->postJson('/api/v1/auth/merchant/login', [
            'email' => $email,
            'password' => $password,
        ]);

        return (string) $login->json('token');
    }
}
