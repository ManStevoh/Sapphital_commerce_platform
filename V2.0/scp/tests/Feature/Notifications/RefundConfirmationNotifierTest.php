<?php

declare(strict_types=1);

namespace Tests\Feature\Notifications;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Modules\Commerce\Orders\Models\Order;
use Platform\Billing\Enums\SubscriptionStatus;
use Platform\Billing\Models\Plan;
use Platform\Billing\Models\Subscription;
use Platform\Tenancy\Models\Tenant;
use Tests\Feature\PlatformTestCase;

final class RefundConfirmationNotifierTest extends PlatformTestCase
{
    public function test_successful_refund_logs_confirmation_in_testing(): void
    {
        Log::spy();

        $tenant = $this->createTenant();
        $merchant = $this->createMerchantForTenant($tenant, 'refund-notify@test.com', 'password12345');
        $this->createActiveSubscription($tenant->id);

        $order = Order::query()->create([
            'tenant_id' => $tenant->id,
            'checkout_session_id' => null,
            'order_number' => 'ORD-RFN-'.Str::upper(Str::random(4)),
            'status' => Order::STATUS_PAID,
            'currency' => 'NGN',
            'subtotal_kobo' => 500_000,
            'total_kobo' => 500_000,
            'customer_email' => 'buyer-refund@example.com',
            'paystack_reference' => 'pay_ref_'.Str::random(8),
        ]);

        $token = $this->loginMerchant($merchant->email, 'password12345');

        $this->postJson(
            "/api/v1/commerce/orders/{$order->id}/refund",
            ['reason' => 'Customer request'],
            array_merge(
                $this->merchantAuthHeaders($tenant->id, $token),
                $this->idempotencyHeaders(),
            ),
        )->assertOk();

        Log::shouldHaveReceived('info')
            ->once()
            ->with('refund.confirmation', \Mockery::subset([
                'order_id' => $order->id,
                'email' => 'buyer-refund@example.com',
                'amount_kobo' => 500_000,
            ]));
    }

    private function createTenant(): Tenant
    {
        return Tenant::query()->create([
            'slug' => 'refund-notify-'.Str::random(6),
            'name' => 'Refund Notify Tenant',
            'status' => 'active',
            'country' => 'NG',
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

    private function loginMerchant(string $email, string $password): string
    {
        $login = $this->postJson('/api/v1/auth/merchant/login', [
            'email' => $email,
            'password' => $password,
        ]);

        return (string) $login->json('token');
    }
}
