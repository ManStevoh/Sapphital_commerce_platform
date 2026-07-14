<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;

final class LaunchReadinessCommand extends Command
{
    protected $signature = 'scp:launch-readiness {--strict : Fail when production env vars are missing}';

    protected $description = 'Run automated Nigeria GA launch readiness checks (engineering blockers)';

    /** @var list<string> */
    private array $failures = [];

    /** @var list<string> */
    private array $warnings = [];

    public function handle(): int
    {
        $this->info('SAPPHITAL SCP — Launch readiness (automated checks)');
        $this->newLine();

        $this->checkIsolationTests();
        $this->checkOpenApiSpec();
        $this->checkAuthzManifest();
        $this->checkScheduler();
        $this->checkSecretsStorage();

        if ($this->option('strict')) {
            $this->checkProductionEnv();
        }

        foreach ($this->warnings as $warning) {
            $this->warn("WARN: {$warning}");
        }

        if ($this->failures !== []) {
            $this->newLine();
            $this->error('Launch readiness FAILED:');

            foreach ($this->failures as $failure) {
                $this->line("  - {$failure}");
            }

            return self::FAILURE;
        }

        $this->newLine();
        $this->info('Launch readiness checks passed.');

        if ($this->warnings !== []) {
            $this->comment('Review warnings above; manual Ch. 12 items still required.');
        }

        return self::SUCCESS;
    }

    private function checkIsolationTests(): void
    {
        $exitCode = Artisan::call('scp:generate-isolation-tests', ['--check' => true]);
        $output = trim(Artisan::output());

        if ($exitCode !== self::SUCCESS) {
            $this->recordFailure('Tenant isolation generated tests are stale or missing.');

            if ($output !== '') {
                $this->warnings[] = $output;
            }

            return;
        }

        $this->line('OK  Tenant isolation test manifest is current.');
    }

    private function checkOpenApiSpec(): void
    {
        $path = base_path('docs/openapi/nigeria-ga-v1.yaml');

        if (! is_file($path)) {
            $this->recordFailure('OpenAPI spec missing at docs/openapi/nigeria-ga-v1.yaml.');

            return;
        }

        $contents = File::get($path);
        $requiredPaths = [
            '/v1/signup',
            '/v1/platform/financial-services/payments/initialize',
            '/v1/commerce/storefront/settings/payments/credentials',
            '/v1/webhooks/paystack',
            '/v1/webhooks/flutterwave',
            '/v1/platform/billing/invoices/{id}/pdf',
        ];

        foreach ($requiredPaths as $apiPath) {
            if (! str_contains($contents, $apiPath.':')) {
                $this->recordFailure("OpenAPI spec missing path {$apiPath}.");

                return;
            }
        }

        $this->line('OK  OpenAPI Nigeria GA spec includes Phase 1 payment paths.');
    }

    private function checkAuthzManifest(): void
    {
        /** @var array<string, array{method: string, uri: string, archetype: string}> $routes */
        $routes = require config_path('authz-routes.php');

        if ($routes === []) {
            $this->recordFailure('Authz route manifest is empty.');

            return;
        }

        $required = [
            'storefront.settings.payments.credentials.show',
            'storefront.settings.payments.credentials.update',
            'financial-services.reconciliation.index',
            'webhooks.flutterwave',
            'identity.auth.merchant.handoff',
        ];

        foreach ($required as $name) {
            if (! isset($routes[$name])) {
                $this->recordFailure("Authz manifest missing route {$name}.");

                return;
            }
        }

        $this->line('OK  Authz manifest covers '.count($routes).' routes.');
    }

    private function checkScheduler(): void
    {
        $required = [
            'scp:process-expired-trials',
            'scp:suspend-overdue-subscriptions',
            'scp:reconcile-nightly',
            'scp:reconcile-pending-payments',
            'scp:alert-dispute-deadlines',
            'cms:process-scheduled-content',
            'catalog:process-scheduled-collections',
            'checkout:expire-gift-cards',
            'tenancy:verify-custom-domains',
        ];

        $consoleRoutes = (string) file_get_contents(base_path('routes/console.php'));

        foreach ($required as $command) {
            if (! str_contains($consoleRoutes, "Schedule::command('{$command}')")) {
                $this->recordFailure("Scheduler missing command {$command}.");

                return;
            }
        }

        $this->line('OK  Scheduler registers billing, payment reconciliation, CMS, and catalog jobs.');
    }

    private function checkSecretsStorage(): void
    {
        $path = (string) config('secrets.paths.default', storage_path('secrets'));

        if (! is_dir($path) && ! mkdir($path, 0700, true) && ! is_dir($path)) {
            $this->recordFailure("Secrets storage path is not writable: {$path}.");

            return;
        }

        if (! is_writable($path)) {
            $this->recordFailure("Secrets storage path is not writable: {$path}.");

            return;
        }

        $this->line('OK  Secrets vault storage path is writable.');
    }

    private function checkProductionEnv(): void
    {
        $required = [
            'APP_KEY' => 'Application encryption key',
            'PAYSTACK_SECRET_KEY' => 'Platform Paystack secret (billing + default checkout)',
        ];

        $recommended = [
            'FLUTTERWAVE_SECRET_KEY' => 'Platform Flutterwave secret',
            'FLUTTERWAVE_SECRET_HASH' => 'Platform Flutterwave webhook hash',
            'TURNSTILE_SECRET_KEY' => 'Bot verification on signup/login',
        ];

        foreach ($required as $key => $label) {
            $value = env($key);

            if (! is_string($value) || $value === '') {
                $this->recordFailure("Missing required env {$key} ({$label}).");
            }
        }

        foreach ($recommended as $key => $label) {
            $value = env($key);

            if (! is_string($value) || $value === '') {
                $this->warnings[] = "Recommended env {$key} is empty ({$label}).";
            }
        }

        if ($this->failures === []) {
            $this->line('OK  Strict production env vars present.');
        }
    }

    private function recordFailure(string $message): void
    {
        $this->failures[] = $message;
    }
}
