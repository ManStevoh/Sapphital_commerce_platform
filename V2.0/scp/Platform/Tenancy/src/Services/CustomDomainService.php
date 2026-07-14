<?php

declare(strict_types=1);

namespace Platform\Tenancy\Services;

use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Platform\Billing\Enums\SubscriptionStatus;
use Platform\Billing\Models\Subscription;
use Platform\Tenancy\Contracts\CustomHostnameSslProvisioner;
use Platform\Tenancy\Contracts\DomainDnsVerifier;
use Platform\Tenancy\Models\CustomDomain;

final class CustomDomainService
{
    public const STATUS_PENDING = 'pending';

    public const STATUS_SSL_PROVISIONING = 'ssl_provisioning';

    public const STATUS_ACTIVE = 'active';

    public const STATUS_FAILED = 'failed';

    public function __construct(
        private readonly DomainDnsVerifier $dns,
        private readonly CustomHostnameSslProvisioner $ssl,
    ) {}

    /**
     * @return list<CustomDomain>
     */
    public function listForTenant(string $tenantId): array
    {
        return CustomDomain::query()
            ->where('tenant_id', $tenantId)
            ->orderByDesc('is_primary')
            ->orderByDesc('created_at')
            ->limit(20)
            ->get()
            ->all();
    }

    public function attach(string $tenantId, string $domain, bool $isPrimary = true): CustomDomain
    {
        $normalized = $this->normalizeDomain($domain);
        $this->assertEntitled($tenantId);

        $existing = CustomDomain::query()->where('domain', $normalized)->first();
        if ($existing !== null && $existing->tenant_id !== $tenantId) {
            throw ValidationException::withMessages([
                'domain' => ['Domain is already attached to another store.'],
            ]);
        }

        if ($existing !== null) {
            return $existing;
        }

        if ($isPrimary) {
            CustomDomain::query()
                ->where('tenant_id', $tenantId)
                ->where('is_primary', true)
                ->update(['is_primary' => false]);
        }

        return CustomDomain::query()->create([
            'tenant_id' => $tenantId,
            'domain' => $normalized,
            'is_primary' => $isPrimary,
            'verification_token' => 'scp_'.Str::lower(Str::random(24)),
            'status' => self::STATUS_PENDING,
        ]);
    }

    public function verify(string $tenantId, string $domainId): CustomDomain
    {
        $domain = CustomDomain::query()
            ->where('tenant_id', $tenantId)
            ->whereKey($domainId)
            ->firstOrFail();

        $check = $this->dns->check(
            $domain->domain,
            $domain->verification_token,
            (string) config('domains.cname_target', 'shops.sapphital.africa'),
        );

        if (! $check['txt_ok'] || ! $check['cname_ok']) {
            $domain->update([
                'status' => self::STATUS_FAILED,
                'verified_at' => null,
            ]);

            throw ValidationException::withMessages([
                'domain' => [implode(' ', $check['details'])],
            ]);
        }

        $domain->update([
            'status' => self::STATUS_SSL_PROVISIONING,
            'verified_at' => now(),
        ]);

        $ssl = $this->ssl->provision($domain->domain, $tenantId);

        $domain->update([
            'status' => $ssl['ok'] ? self::STATUS_ACTIVE : self::STATUS_FAILED,
        ]);

        return $domain->fresh();
    }

    public function detach(string $tenantId, string $domainId): void
    {
        $domain = CustomDomain::query()
            ->where('tenant_id', $tenantId)
            ->whereKey($domainId)
            ->firstOrFail();

        $domain->delete();
    }

    public function resolveTenantIdByHost(string $host): ?string
    {
        $domain = strtolower(trim(explode(':', $host)[0] ?? ''));
        if ($domain === '') {
            return null;
        }

        return CustomDomain::query()
            ->where('domain', $domain)
            ->where('status', self::STATUS_ACTIVE)
            ->value('tenant_id');
    }

    /**
     * @return array{txt_host: string, txt_value: string, cname_host: string, cname_target: string}
     */
    public function dnsInstructions(CustomDomain $domain): array
    {
        $prefix = (string) config('domains.txt_host_prefix', '_sapphital-verify');

        return [
            'txt_host' => $prefix.'.'.$domain->domain,
            'txt_value' => $domain->verification_token,
            'cname_host' => $domain->domain,
            'cname_target' => (string) config('domains.cname_target', 'shops.sapphital.africa'),
        ];
    }

    private function assertEntitled(string $tenantId): void
    {
        $subscription = Subscription::query()
            ->where('tenant_id', $tenantId)
            ->where('status', SubscriptionStatus::Active)
            ->with('plan')
            ->latest()
            ->first();

        $plan = $subscription?->plan;
        $slug = is_string($plan?->slug) ? $plan->slug : 'starter';
        $allowsCustom = (bool) ($plan?->custom_domain ?? false);
        $limit = (int) (config('domains.plan_limits.'.$slug)
            ?? config('domains.plan_limits.default', 0));

        if (! $allowsCustom || $limit <= 0) {
            throw ValidationException::withMessages([
                'domain' => ['Custom domains require a Growth plan or higher.'],
            ]);
        }

        $count = CustomDomain::query()->where('tenant_id', $tenantId)->count();
        if ($count >= $limit) {
            throw ValidationException::withMessages([
                'domain' => ["Plan {$slug} allows {$limit} custom domain(s)."],
            ]);
        }
    }

    private function normalizeDomain(string $domain): string
    {
        $normalized = strtolower(trim($domain));
        $normalized = preg_replace('#^https?://#', '', $normalized) ?? $normalized;
        $normalized = rtrim($normalized, '/');
        $normalized = explode('/', $normalized)[0] ?? $normalized;
        $normalized = explode(':', $normalized)[0] ?? $normalized;

        if ($normalized === '' || ! str_contains($normalized, '.')) {
            throw ValidationException::withMessages([
                'domain' => ['Enter a valid hostname (e.g. www.merchant.ng).'],
            ]);
        }

        if (str_ends_with($normalized, '.shops.sapphital.africa')
            || str_ends_with($normalized, '.shops.sapphital.test')) {
            throw ValidationException::withMessages([
                'domain' => ['Platform subdomains cannot be registered as custom domains.'],
            ]);
        }

        return $normalized;
    }
}
