<?php

declare(strict_types=1);

namespace Platform\FinancialServices\Tests\Feature\FinancialServices;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Str;
use Modules\Commerce\Orders\Models\Order;
use Platform\FinancialServices\Enums\DisputeStatus;
use Platform\FinancialServices\Models\Dispute;
use Platform\Identity\Enums\MerchantUserRole;
use Platform\Identity\Models\MerchantUser;
use Platform\Tenancy\Models\Tenant;
use Tests\Feature\PlatformTestCase;

final class DisputeDeadlineNotifierTest extends PlatformTestCase
{
    public function test_alert_command_marks_dispute_alerted_and_is_idempotent(): void
    {
        $tenant = Tenant::query()->create([
            'slug' => 'notify-'.Str::random(6),
            'name' => 'Notify Store',
            'status' => 'active',
            'country' => 'NG',
        ]);

        MerchantUser::query()->create([
            'tenant_id' => $tenant->id,
            'email' => 'owner@notify.test',
            'password' => 'password12345',
            'role' => MerchantUserRole::Owner,
        ]);

        $order = Order::query()->create([
            'tenant_id' => $tenant->id,
            'checkout_session_id' => null,
            'order_number' => 'ORD-NOT-'.Str::upper(Str::random(4)),
            'status' => Order::STATUS_PAID,
            'currency' => 'NGN',
            'subtotal_kobo' => 500_000,
            'total_kobo' => 500_000,
            'paystack_reference' => 'pay_notify_'.Str::random(6),
        ]);

        $dispute = Dispute::query()->create([
            'tenant_id' => $tenant->id,
            'order_id' => $order->id,
            'type' => 'chargeback',
            'provider' => 'paystack',
            'status' => DisputeStatus::Open,
            'provider_case_id' => 'notify-'.Str::random(4),
            'amount_kobo' => 500_000,
            'currency' => 'NGN',
            'paystack_reference' => $order->paystack_reference,
            'due_at' => now()->addHours(24),
        ]);

        Artisan::call('scp:alert-dispute-deadlines');
        $this->assertStringContainsString('Disputes due within 48h: 1', Artisan::output());

        $dispute->refresh();
        $this->assertNotNull($dispute->deadline_alerted_at);

        Artisan::call('scp:alert-dispute-deadlines');
        $this->assertStringContainsString('Disputes due within 48h: 0', Artisan::output());
    }
}
