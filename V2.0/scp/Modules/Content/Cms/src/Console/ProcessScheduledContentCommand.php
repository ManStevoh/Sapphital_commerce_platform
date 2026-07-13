<?php

declare(strict_types=1);

namespace Modules\Content\Cms\Console;

use Illuminate\Console\Command;
use Modules\Content\Cms\Services\ProcessScheduledContentService;

final class ProcessScheduledContentCommand extends Command
{
    protected $signature = 'cms:process-scheduled-content';

    protected $description = 'Publish scheduled CMS pages/posts and unpublish content past its scheduled end time';

    public function handle(ProcessScheduledContentService $service): int
    {
        $count = $service->run();

        if ($count === 0) {
            $this->info('No scheduled CMS content required processing.');

            return self::SUCCESS;
        }

        $this->info("Processed {$count} scheduled CMS content item(s).");

        return self::SUCCESS;
    }
}
