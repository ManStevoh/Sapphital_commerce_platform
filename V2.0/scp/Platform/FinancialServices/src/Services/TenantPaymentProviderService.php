<?php

declare(strict_types=1);

namespace Platform\FinancialServices\Services;

use Platform\Tenancy\Models\Tenant;

final class TenantPaymentProviderService
{
    /**
     * @return 'paystack'|'flutterwave'
     */
    public function forTenant(string $tenantId): string
    {
        $tenant = Tenant::query()->find($tenantId);
        $settings = is_array($tenant?->settings) ? $tenant->settings : [];
        $provider = $settings['payment_provider'] ?? null;

        if ($provider === 'flutterwave') {
            return 'flutterwave';
        }

        if ($provider === 'paystack') {
            return 'paystack';
        }

        $default = config('payments.default_provider', 'paystack');

        return $default === 'flutterwave' ? 'flutterwave' : 'paystack';
    }
}
