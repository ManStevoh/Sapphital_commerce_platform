<?php

declare(strict_types=1);

namespace Platform\FinancialServices\Console;

use Illuminate\Console\Command;
use Platform\FinancialServices\Services\DisputeDeadlineAlertService;

final class AlertDisputeDeadlinesCommand extends Command
{
    protected $signature = 'scp:alert-dispute-deadlines {--hours=48 : Alert window before evidence due}';

    protected $description = 'Alert merchants about chargeback disputes due within the configured window';

    public function handle(DisputeDeadlineAlertService $alerts): int
    {
        $hours = max(1, (int) $this->option('hours'));
        $due = $alerts->run($hours);

        $this->info('Dispute deadline scan complete.');
        $this->line('  Disputes due within '.$hours.'h: '.count($due));

        foreach ($due as $item) {
            $this->line("  - {$item['id']} (order {$item['order_id']}) due {$item['due_at']}");
        }

        return self::SUCCESS;
    }
}
