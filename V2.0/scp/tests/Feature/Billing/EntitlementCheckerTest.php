<?php

declare(strict_types=1);

namespace Tests\Feature\Billing;

use Illuminate\Support\Str;
use Platform\Billing\Contracts\TenantProductCounter;
use Platform\Billing\Enums\SubscriptionStatus;
use Platform\Billing\Models\Plan;
use Platform\Billing\Models\Subscription;
use Platform\Billing\Services\EntitlementChecker;
use Tests\Feature\PlatformTestCase;

final class EntitlementCheckerTest extends PlatformTestCase
{
    public function test_entitlement_checker_respects_starter_product_limit(): void
    {
        $tenantId = (string) Str::uuid();
        $starterPlan = Plan::query()->where('slug', 'starter')->firstOrFail();

        Subscription::query()->create([
            'tenant_id' => $tenantId,
            'plan_id' => $starterPlan->id,
            'status' => SubscriptionStatus::Active,
        ]);

        $this->app->instance(TenantProductCounter::class, new class(99) implements TenantProductCounter {
            public function __construct(private readonly int $count) {}

            public function count(string $tenantId): int
            {
                return $this->count;
            }
        });

        $checker = $this->app->make(EntitlementChecker::class);

        $this->assertTrue($checker->canAddProduct($tenantId));

        $this->app->instance(TenantProductCounter::class, new class(100) implements TenantProductCounter {
            public function __construct(private readonly int $count) {}

            public function count(string $tenantId): int
            {
                return $this->count;
            }
        });

        $this->app->forgetInstance(EntitlementChecker::class);

        $checker = $this->app->make(EntitlementChecker::class);

        $this->assertFalse($checker->canAddProduct($tenantId));
    }
}
