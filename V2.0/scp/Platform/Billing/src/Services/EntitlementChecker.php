<?php

declare(strict_types=1);

namespace Platform\Billing\Services;

use Platform\Billing\Contracts\TenantProductCounter;
use Platform\Billing\Enums\SubscriptionStatus;
use Platform\Billing\Models\Subscription;

final class EntitlementChecker
{
    public function __construct(
        private readonly TenantProductCounter $productCounter,
    ) {}

    public function canAddProduct(string $tenantId): bool
    {
        $subscription = Subscription::query()
            ->where('tenant_id', $tenantId)
            ->whereIn('status', [
                SubscriptionStatus::Trial,
                SubscriptionStatus::Active,
            ])
            ->with('plan')
            ->first();

        if ($subscription === null || $subscription->plan === null) {
            return false;
        }

        return $this->productCounter->count($tenantId) < $subscription->plan->product_limit;
    }
}
