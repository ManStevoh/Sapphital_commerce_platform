<?php

declare(strict_types=1);

namespace Modules\Commerce\Orders\Services;

use Platform\Tenancy\Models\Tenant;

final class StoreSettingsService
{
    public function __construct(
        private readonly ReturnWindowService $returnWindow,
    ) {}

    /**
     * @return array{
     *     return_window_days: int,
     *     currency: string,
     *     timezone: string,
     *     payment_provider: string
     * }
     */
    public function getForTenant(string $tenantId): array
    {
        $tenant = Tenant::query()->findOrFail($tenantId);
        $settings = is_array($tenant->settings) ? $tenant->settings : [];

        return [
            'return_window_days' => $this->returnWindow->windowDaysForTenant($tenantId),
            'currency' => is_string($settings['currency'] ?? null) ? $settings['currency'] : 'NGN',
            'timezone' => is_string($settings['timezone'] ?? null) ? $settings['timezone'] : 'Africa/Lagos',
            'payment_provider' => $this->paymentProviderFromSettings($settings),
        ];
    }

    /**
     * @return array{payment_provider: string, currency: string}
     */
    public function getCheckoutSettingsForTenant(string $tenantId): array
    {
        $tenant = Tenant::query()->findOrFail($tenantId);
        $settings = is_array($tenant->settings) ? $tenant->settings : [];

        return [
            'payment_provider' => $this->paymentProviderFromSettings($settings),
            'currency' => is_string($settings['currency'] ?? null) ? $settings['currency'] : 'NGN',
        ];
    }

    public function updatePaymentProvider(string $tenantId, string $provider): string
    {
        if (! in_array($provider, ['paystack', 'flutterwave'], true)) {
            throw new \InvalidArgumentException('Unsupported payment provider.');
        }

        $tenant = Tenant::query()->findOrFail($tenantId);
        $settings = is_array($tenant->settings) ? $tenant->settings : [];
        $settings['payment_provider'] = $provider;

        $tenant->update(['settings' => $settings]);

        return $provider;
    }

    public function updateReturnWindow(string $tenantId, int $days): int
    {
        $clamped = max(ReturnWindowService::MIN_DAYS, min(ReturnWindowService::MAX_DAYS, $days));

        $tenant = Tenant::query()->findOrFail($tenantId);
        $settings = is_array($tenant->settings) ? $tenant->settings : [];
        $settings['return_window_days'] = $clamped;

        $tenant->update(['settings' => $settings]);

        return $clamped;
    }

    /**
     * @param  array<string, mixed>  $settings
     */
    private function paymentProviderFromSettings(array $settings): string
    {
        $provider = $settings['payment_provider'] ?? null;

        return $provider === 'flutterwave' ? 'flutterwave' : 'paystack';
    }
}
