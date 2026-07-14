<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

final class SyntheticCheckoutProbeCommand extends Command
{
    protected $signature = 'ops:synthetic-checkout-probe
        {--base-url= : Public base URL to probe}
        {--dry-run : Print probe plan without network calls}';

    protected $description = 'Run Nigeria synthetic probes for status page checkout readiness';

    public function handle(): int
    {
        $baseUrl = rtrim((string) ($this->option('base-url') ?: config('app.url')), '/');
        $probes = [
            'ready' => $baseUrl.'/api/ready',
            'themes' => $baseUrl.'/api/v1/commerce/storefront/themes',
        ];

        if ($this->option('dry-run')) {
            foreach ($probes as $name => $url) {
                $this->line("probe={$name} url={$url}");
            }

            return self::SUCCESS;
        }

        $failed = [];

        foreach ($probes as $name => $url) {
            $started = microtime(true);
            $response = Http::timeout(5)->acceptJson()->get($url);
            $latencyMs = (int) round((microtime(true) - $started) * 1000);

            $this->line("probe={$name} status={$response->status()} latency_ms={$latencyMs}");

            if (! $response->successful()) {
                $failed[] = $name;
            }
        }

        if ($failed !== []) {
            $this->error('Synthetic probe failed: '.implode(', ', $failed));

            return self::FAILURE;
        }

        $this->info('Synthetic checkout probe passed.');

        return self::SUCCESS;
    }
}
