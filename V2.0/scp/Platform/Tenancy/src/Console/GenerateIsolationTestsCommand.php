<?php

declare(strict_types=1);

namespace Platform\Tenancy\Console;

use Illuminate\Console\Command;
use Platform\Tenancy\Testing\GeneratedIsolationTestChecker;
use Platform\Tenancy\Testing\IsolationTestGenerator;

final class GenerateIsolationTestsCommand extends Command
{
    protected $signature = 'scp:generate-isolation-tests {--check : Fail if generated files are stale}';

    protected $description = 'Generate per-model tenant isolation tests from config/tenant-isolation.php';

    public function handle(
        IsolationTestGenerator $generator,
        GeneratedIsolationTestChecker $checker,
    ): int {
        if ($this->option('check')) {
            $stale = $checker->staleFiles();

            if ($stale !== []) {
                $this->error('Generated isolation tests are stale or missing:');

                foreach ($stale as $path) {
                    $this->line(' - '.$path);
                }

                $this->line('');
                $this->line('Run: php artisan scp:generate-isolation-tests');

                return self::FAILURE;
            }

            $this->info('Generated isolation tests are up to date.');

            return self::SUCCESS;
        }

        $written = $generator->writeFiles();

        $this->info('Generated '.count($written).' isolation test file(s).');

        foreach ($written as $path) {
            $this->line(' - '.$path);
        }

        return self::SUCCESS;
    }
}
