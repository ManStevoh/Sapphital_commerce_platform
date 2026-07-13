<?php

declare(strict_types=1);

namespace Platform\Billing\Services;

use Platform\Billing\Models\Invoice;
use Platform\Billing\Support\SimplePdfBuilder;
use Platform\Tenancy\Models\Tenant;

final class InvoicePdfService
{
    public function __construct(
        private readonly SimplePdfBuilder $pdfBuilder,
    ) {}

    public function render(Invoice $invoice, Tenant $tenant): string
    {
        $lines = [
            'Invoice: '.$invoice->number,
            'Tenant: '.$tenant->name,
            'Status: '.$invoice->status->value,
            'Currency: '.$invoice->currency,
            'Period: '.$invoice->period_start?->toDateString().' to '.$invoice->period_end?->toDateString(),
            '',
        ];

        $invoiceLines = is_array($invoice->lines) ? $invoice->lines : [];

        foreach ($invoiceLines as $line) {
            if (! is_array($line)) {
                continue;
            }

            $description = is_string($line['description'] ?? null) ? $line['description'] : 'Line item';
            $amount = is_int($line['amount'] ?? null) ? $line['amount'] : 0;
            $lines[] = $description.' — '.$this->formatNgn($amount);
        }

        $lines[] = '';
        $lines[] = 'Subtotal: '.$this->formatNgn((int) $invoice->subtotal);

        if ((int) $invoice->tax > 0) {
            $vatRate = (float) config('billing.vat_rate', 0.075) * 100;
            $lines[] = sprintf('VAT (%.1f%%): %s', $vatRate, $this->formatNgn((int) $invoice->tax));
        }

        $lines[] = 'Total: '.$this->formatNgn((int) $invoice->total);

        if (is_string($invoice->paystack_reference) && $invoice->paystack_reference !== '') {
            $lines[] = 'Payment reference: '.$invoice->paystack_reference;
        }

        $title = (string) config('billing.platform_name', 'SAPPHITAL Commerce Platform');

        return $this->pdfBuilder->build($title.' — Tax Invoice', $lines);
    }

    private function formatNgn(int $kobo): string
    {
        $naira = $kobo / 100;

        return 'NGN '.number_format($naira, 2);
    }
}
