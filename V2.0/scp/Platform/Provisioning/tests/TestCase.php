<?php

declare(strict_types=1);

namespace Platform\Provisioning\Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Orchestra\Testbench\TestCase as OrchestraTestCase;
use Platform\Billing\BillingServiceProvider;
use Platform\Provisioning\ProvisioningServiceProvider;
use Platform\Tenancy\TenancyServiceProvider;

abstract class TestCase extends OrchestraTestCase
{
    use RefreshDatabase;

    /**
     * @param  \Illuminate\Foundation\Application  $app
     * @return array<int, class-string>
     */
    protected function getPackageProviders($app): array
    {
        return [
            TenancyServiceProvider::class,
            BillingServiceProvider::class,
            ProvisioningServiceProvider::class,
        ];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('queue.default', 'sync');
    }

    protected function defineDatabaseMigrations(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../../Tenancy/database/migrations');
        $this->loadMigrationsFrom(__DIR__.'/../../Billing/database/migrations');
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
        $this->loadMigrationsFrom(__DIR__.'/../../Identity/database/migrations/2026_07_12_100001_create_merchant_users_table.php');
    }
}
