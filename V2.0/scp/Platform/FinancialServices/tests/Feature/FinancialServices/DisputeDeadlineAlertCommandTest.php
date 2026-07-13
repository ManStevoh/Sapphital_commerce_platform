<?php

declare(strict_types=1);

namespace Platform\FinancialServices\Tests\Feature\FinancialServices;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Str;
use Modules\Commerce\Orders\Models\Order;
use Platform\FinancialServices\Enums\DisputeStatus;
use Platform\FinancialServices\Models\Dispute;
use Platform\Tenancy\Models\Tenant;
use Tests\Feature\PlatformTestCase;

final class DisputeDeadlineAlertCommandTest extends PlatformTestCase
{
    public function test_command_reports_disputes_due_within_window(): void
    {
        $tenant = Tenant::query()->create([
            'slug' => 'deadline-'.Str::random(6),
            'name' => 'Deadline Tenant',
            'status' => 'active',
            'country' => 'NG',
        ]);

        $order = Order::query()->create([
            'tenant_id' => $tenant->id,
            'checkout_session_id' => null,
            'order_number' => 'ORD-DUE-'.Str::upper(Str::random(4)),
            'status' => Order::STATUS_PAID,
            'currency' => 'NGN',
            'subtotal_kobo' => 500_000,
            'total_kobo' => 500_000,
            'paystack_reference' => 'pay_due_'.Str::random(6),
        ]);

        Dispute::query()->create([
            'tenant_id' => $tenant->id,
            'order_id' => $order->id,
            'type' => 'chargeback',
            'provider' => 'paystack',
            'status' => DisputeStatus::Open,
            'provider_case_id' => 'due-'.Str::random(4),
            'amount_kobo' => 500_000,
            'currency' => 'NGN',
            'paystack_reference' => $order->paystack_reference,
            'due_at' => now()->addHours(24),
        ]);

        $exitCode = Artisan::call('scp:alert-dispute-deadlines');

        $this->assertSame(0, $exitCode);
        $this->assertStringContainsString('Disputes due within 48h: 1', Artisan::output());
    }
}
