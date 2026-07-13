<?php

declare(strict_types=1);

namespace Platform\FinancialServices\Services;

use Platform\Secrets\Contracts\SecretVaultInterface;

final class TenantPaymentCredentialsService
{
    public function __construct(
        private readonly SecretVaultInterface $vault,
    ) {}

    public function secretKeyFor(string $tenantId, string $provider): string
    {
        $tenantSecret = $this->vault->get($this->secretKeyVaultKey($tenantId, $provider));

        if (is_string($tenantSecret) && $tenantSecret !== '') {
            return $tenantSecret;
        }

        return $this->platformSecretKey($provider);
    }

    public function webhookHashFor(string $tenantId, string $provider): string
    {
        if ($provider !== 'flutterwave') {
            return '';
        }

        $tenantHash = $this->vault->get($this->webhookHashVaultKey($tenantId, $provider));

        if (is_string($tenantHash) && $tenantHash !== '') {
            return $tenantHash;
        }

        $platformHash = config('flutterwave.secret_hash');

        return is_string($platformHash) ? $platformHash : '';
    }

    public function hasTenantSecretKey(string $tenantId, string $provider): bool
    {
        $tenantSecret = $this->vault->get($this->secretKeyVaultKey($tenantId, $provider));

        return is_string($tenantSecret) && $tenantSecret !== '';
    }

    public function hasTenantWebhookHash(string $tenantId, string $provider): bool
    {
        if ($provider !== 'flutterwave') {
            return false;
        }

        $tenantHash = $this->vault->get($this->webhookHashVaultKey($tenantId, $provider));

        return is_string($tenantHash) && $tenantHash !== '';
    }

    public function storeSecretKey(string $tenantId, string $provider, string $secretKey): void
    {
        $this->vault->set($this->secretKeyVaultKey($tenantId, $provider), $secretKey);
    }

    public function storeWebhookHash(string $tenantId, string $provider, string $secretHash): void
    {
        if ($provider !== 'flutterwave') {
            return;
        }

        $this->vault->set($this->webhookHashVaultKey($tenantId, $provider), $secretHash);
    }

    public function clearSecretKey(string $tenantId, string $provider): void
    {
        $this->vault->set($this->secretKeyVaultKey($tenantId, $provider), '');
    }

    public function clearWebhookHash(string $tenantId, string $provider): void
    {
        if ($provider !== 'flutterwave') {
            return;
        }

        $this->vault->set($this->webhookHashVaultKey($tenantId, $provider), '');
    }

    /**
     * @return array{
     *     paystack: array{configured: bool, masked_secret_key: ?string, uses_platform_key: bool},
     *     flutterwave: array{
     *         configured: bool,
     *         masked_secret_key: ?string,
     *         webhook_hash_configured: bool,
     *         uses_platform_key: bool
     *     }
     * }
     */
    public function statusForTenant(string $tenantId): array
    {
        $paystackConfigured = $this->hasTenantSecretKey($tenantId, 'paystack');
        $flutterwaveConfigured = $this->hasTenantSecretKey($tenantId, 'flutterwave');
        $flutterwaveHashConfigured = $this->hasTenantWebhookHash($tenantId, 'flutterwave');

        return [
            'paystack' => [
                'configured' => $paystackConfigured,
                'masked_secret_key' => $paystackConfigured
                    ? $this->maskSecret($this->secretKeyFor($tenantId, 'paystack'))
                    : null,
                'uses_platform_key' => ! $paystackConfigured && $this->platformSecretKey('paystack') !== '',
            ],
            'flutterwave' => [
                'configured' => $flutterwaveConfigured,
                'masked_secret_key' => $flutterwaveConfigured
                    ? $this->maskSecret($this->secretKeyFor($tenantId, 'flutterwave'))
                    : null,
                'webhook_hash_configured' => $flutterwaveHashConfigured,
                'uses_platform_key' => ! $flutterwaveConfigured && $this->platformSecretKey('flutterwave') !== '',
            ],
        ];
    }

    public function maskSecret(string $secret): string
    {
        $length = strlen($secret);

        if ($length <= 8) {
            return str_repeat('*', $length);
        }

        return substr($secret, 0, 4).str_repeat('*', max(4, $length - 8)).substr($secret, -4);
    }

    private function secretKeyVaultKey(string $tenantId, string $provider): string
    {
        return "tenant.{$tenantId}.{$provider}.secret_key";
    }

    private function webhookHashVaultKey(string $tenantId, string $provider): string
    {
        return "tenant.{$tenantId}.{$provider}.secret_hash";
    }

    private function platformSecretKey(string $provider): string
    {
        $configKey = match ($provider) {
            'paystack' => 'paystack.secret_key',
            'flutterwave' => 'flutterwave.secret_key',
            default => null,
        };

        if ($configKey === null) {
            return '';
        }

        $secret = config($configKey);

        return is_string($secret) ? $secret : '';
    }
}
