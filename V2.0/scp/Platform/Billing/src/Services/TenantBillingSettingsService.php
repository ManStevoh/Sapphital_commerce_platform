<?php

declare(strict_types=1);

namespace Platform\Billing\Services;

use Platform\Tenancy\Models\Tenant;

final class TenantBillingSettingsService
{
    /**
     * @return array{vat_registered: bool, currency: string}
     */
    public function getForTenant(string $tenantId): array
    {
        $tenant = Tenant::query()->findOrFail($tenantId);
        $settings = is_array($tenant->settings) ? $tenant->settings : [];

        return [
            'vat_registered' => (bool) ($settings['vat_registered'] ?? false),
            'currency' => is_string($settings['currency'] ?? null) ? $settings['currency'] : 'NGN',
        ];
    }

    public function updateVatRegistered(string $tenantId, bool $vatRegistered): bool
    {
        $tenant = Tenant::query()->findOrFail($tenantId);
        $settings = is_array($tenant->settings) ? $tenant->settings : [];
        $settings['vat_registered'] = $vatRegistered;

        $tenant->update(['settings' => $settings]);

        return $vatRegistered;
    }
}
