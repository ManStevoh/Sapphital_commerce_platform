<?php

declare(strict_types=1);

namespace Modules\Commerce\Catalog\Console;

use Illuminate\Console\Command;
use Modules\Commerce\Catalog\Services\ProcessScheduledCollectionsService;

final class ProcessScheduledCollectionsCommand extends Command
{
    protected $signature = 'catalog:process-scheduled-collections';

    protected $description = 'Publish and unpublish collections based on start/end schedule';

    public function handle(ProcessScheduledCollectionsService $service): int
    {
        $count = $service->run();

        if ($count === 0) {
            $this->info('No scheduled collections required processing.');

            return self::SUCCESS;
        }

        $this->info("Processed {$count} scheduled collection(s).");

        return self::SUCCESS;
    }
}
