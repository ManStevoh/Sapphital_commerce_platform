<?php

declare(strict_types=1);

namespace Platform\Billing\Console;

use Illuminate\Console\Command;
use Platform\Billing\Services\SubscriptionLifecycleService;

final class SuspendOverdueSubscriptionsCommand extends Command
{
    protected $signature = 'scp:suspend-overdue-subscriptions {--days=14 : Grace period after past_due before suspend}';

    protected $description = 'Suspend subscriptions and storefronts overdue past the dunning grace window';

    public function handle(SubscriptionLifecycleService $lifecycle): int
    {
        $days = max(1, (int) $this->option('days'));
        $count = $lifecycle->suspendOverdueSubscriptions($days);

        if ($count === 0) {
            $this->info('No overdue subscriptions to suspend.');

            return self::SUCCESS;
        }

        $this->info("Suspended {$count} overdue subscription(s) and tenant storefront(s).");

        return self::SUCCESS;
    }
}
