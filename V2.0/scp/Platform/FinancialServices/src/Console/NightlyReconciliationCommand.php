<?php

declare(strict_types=1);

namespace Platform\FinancialServices\Console;

use Illuminate\Console\Command;
use Platform\FinancialServices\Services\NightlyReconciliationService;

final class NightlyReconciliationCommand extends Command
{
    protected $signature = 'scp:reconcile-nightly {--minutes=15 : Stale pending session threshold}';

    protected $description = 'Nightly payment reconciliation — stale checkouts, orphan session audit';

    public function handle(NightlyReconciliationService $reconciliation): int
    {
        $minutes = max(1, (int) $this->option('minutes'));

        $summary = $reconciliation->run($minutes);

        $this->info('Nightly reconciliation complete.');
        $this->line("  Pending sessions reconciled: {$summary['pending_reconciled']}");
        $this->line("  Pending reconciliation failures: {$summary['pending_failed']}");
        $this->line("  Paid orders with incomplete checkout session: {$summary['paid_orders_missing_completed_session']}");
        $this->line("  Completed checkout sessions without order: {$summary['completed_sessions_missing_order']}");

        if ($summary['paid_orders_missing_completed_session'] > 0
            || $summary['completed_sessions_missing_order'] > 0) {
            $this->warn('Data inconsistencies detected — review finance ops queue.');
        }

        return self::SUCCESS;
    }
}
