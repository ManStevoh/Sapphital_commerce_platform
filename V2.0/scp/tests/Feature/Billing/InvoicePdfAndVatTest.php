<?php

declare(strict_types=1);

namespace Tests\Feature\Billing;

use Platform\Billing\Enums\InvoiceStatus;
use Platform\Billing\Enums\SubscriptionStatus;
use Platform\Billing\Models\Invoice;
use Platform\Billing\Models\Plan;
use Platform\Billing\Models\Subscription;
use Platform\Billing\Services\BillingTaxService;
use Platform\Billing\Services\InvoicePdfService;
use Platform\Billing\Services\SubscriptionLifecycleService;
use Platform\Tenancy\Models\Tenant;
use Tests\Feature\PlatformTestCase;

final class InvoicePdfAndVatTest extends PlatformTestCase
{
    public function test_vat_registered_tenant_invoice_includes_tax_line(): void
    {
        $tenant = Tenant::query()->create([
            'slug' => 'vat-tenant',
            'name' => 'VAT Tenant',
            'status' => 'active',
            'country' => 'NG',
            'settings' => ['vat_registered' => true],
        ]);

        $plan = Plan::query()->where('slug', 'starter')->firstOrFail();

        Subscription::query()->create([
            'tenant_id' => $tenant->id,
            'plan_id' => $plan->id,
            'status' => SubscriptionStatus::Trial,
        ]);

        $result = app(SubscriptionLifecycleService::class)->activateAfterPayment($tenant->id, 'saas_vat_ref');

        $invoice = $result['invoice'];
        $this->assertNotNull($invoice);
        $this->assertGreaterThan(0, $invoice->tax);
        $this->assertSame($plan->price_ngn + $invoice->tax, $invoice->total);

        $amounts = app(BillingTaxService::class)->calculateForTenant($tenant, $plan->price_ngn);
        $this->assertTrue($amounts['vat_applied']);
    }

    public function test_merchant_can_download_invoice_pdf(): void
    {
        $tenant = Tenant::query()->create([
            'slug' => 'pdf-tenant',
            'name' => 'PDF Tenant',
            'status' => 'active',
            'country' => 'NG',
        ]);

        $this->createMerchantForTenant($tenant, 'pdf@test.com', 'password12345');
        $token = (string) $this->postJson('/api/v1/auth/merchant/login', [
            'email' => 'pdf@test.com',
            'password' => 'password12345',
        ])->json('token');

        $invoice = Invoice::query()->create([
            'tenant_id' => $tenant->id,
            'number' => 'INV-2026-00099',
            'status' => InvoiceStatus::Paid,
            'currency' => 'NGN',
            'subtotal' => 1_500_000,
            'tax' => 112_500,
            'total' => 1_612_500,
            'period_start' => now()->startOfMonth(),
            'period_end' => now()->addMonth()->startOfMonth(),
            'lines' => [
                ['description' => 'Starter subscription', 'amount' => 1_500_000],
                ['description' => 'VAT (7.5%)', 'amount' => 112_500],
            ],
        ]);

        $response = $this->get(
            "/api/v1/platform/billing/invoices/{$invoice->id}/pdf",
            $this->merchantAuthHeaders($tenant->id, $token),
        );

        $response->assertOk();
        $this->assertStringStartsWith('%PDF', $response->streamedContent());

        $pdf = app(InvoicePdfService::class)->render($invoice, $tenant);
        $this->assertStringContainsString('VAT', $pdf);
        $this->assertStringContainsString('7.5%', $pdf);
    }
}
