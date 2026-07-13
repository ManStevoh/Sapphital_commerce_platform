<?php

declare(strict_types=1);

namespace Platform\Billing\Console;

use Illuminate\Console\Command;
use Platform\Billing\Services\SubscriptionLifecycleService;

final class ProcessExpiredTrialsCommand extends Command
{
    protected $signature = 'scp:process-expired-trials';

    protected $description = 'Mark expired trial subscriptions as past_due (dunning entry point)';

    public function handle(SubscriptionLifecycleService $lifecycle): int
    {
        $count = $lifecycle->processExpiredTrials();

        if ($count === 0) {
            $this->info('No expired trial subscriptions found.');

            return self::SUCCESS;
        }

        $this->info("Marked {$count} trial subscription(s) as past_due.");

        return self::SUCCESS;
    }
}
