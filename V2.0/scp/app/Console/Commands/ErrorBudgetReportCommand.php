<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Support\Ops\ErrorBudgetCalculator;
use Illuminate\Console\Command;

final class ErrorBudgetReportCommand extends Command
{
    protected $signature = 'ops:error-budget-report
        {--availability=100 : Platform availability percentage}
        {--checkout=100 : Checkout availability percentage}
        {--webhooks=100 : Webhook delivery percentage}
        {--json : Emit JSON for dashboards}';

    protected $description = 'Calculate monthly SLO error-budget policy state';

    public function handle(ErrorBudgetCalculator $calculator): int
    {
        $report = $calculator->report([
            'availability' => (float) $this->option('availability'),
            'checkout_availability' => (float) $this->option('checkout'),
            'webhook_delivery' => (float) $this->option('webhooks'),
        ]);

        if ($this->option('json')) {
            $this->line(json_encode($report, JSON_THROW_ON_ERROR));

            return self::SUCCESS;
        }

        $this->info('SLO error budget report');
        $this->line('window='.$report['window']);
        $this->line('lowest_budget_remaining_percent='.$report['lowest_budget_remaining_percent']);
        $this->line('policy_state='.$report['policy']['state']);
        $this->line('policy_action='.$report['policy']['action']);

        return self::SUCCESS;
    }
}
