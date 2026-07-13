<?php

declare(strict_types=1);

namespace Platform\Billing\Services;

use Platform\Tenancy\Models\Tenant;

final class BillingTaxService
{
    /**
     * @return array{subtotal: int, tax: int, total: int, vat_applied: bool, vat_rate: float}
     */
    public function calculateForTenant(Tenant $tenant, int $subtotalKobo): array
    {
        $settings = is_array($tenant->settings) ? $tenant->settings : [];
        $vatRegistered = (bool) ($settings['vat_registered'] ?? false);
        $vatRate = (float) config('billing.vat_rate', 0.075);

        if (! $vatRegistered || $subtotalKobo <= 0) {
            return [
                'subtotal' => $subtotalKobo,
                'tax' => 0,
                'total' => $subtotalKobo,
                'vat_applied' => false,
                'vat_rate' => $vatRate,
            ];
        }

        $tax = (int) round($subtotalKobo * $vatRate);
        $total = $subtotalKobo + $tax;

        return [
            'subtotal' => $subtotalKobo,
            'tax' => $tax,
            'total' => $total,
            'vat_applied' => true,
            'vat_rate' => $vatRate,
        ];
    }
}
