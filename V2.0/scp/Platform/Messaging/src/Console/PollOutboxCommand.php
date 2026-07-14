<?php

declare(strict_types=1);

namespace Platform\Messaging\Console;

use Illuminate\Console\Command;
use Platform\Messaging\Services\OutboxPoller;

final class PollOutboxCommand extends Command
{
    protected $signature = 'messaging:poll-outbox {--batch=100 : Max unpublished rows to claim}';

    protected $description = 'Publish unpublished outbox events to merchant webhook endpoints';

    public function handle(OutboxPoller $poller): int
    {
        $batch = max(1, min(500, (int) $this->option('batch')));
        $stats = $poller->poll($batch);

        $this->info(sprintf(
            'Outbox poll complete: processed=%d published=%d failed=%d dead_lettered=%d',
            $stats['processed'],
            $stats['published'],
            $stats['failed'],
            $stats['dead_lettered'],
        ));

        return self::SUCCESS;
    }
}
