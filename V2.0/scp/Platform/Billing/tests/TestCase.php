<?php

declare(strict_types=1);

namespace Platform\Billing\Tests;

use Orchestra\Testbench\TestCase as OrchestraTestCase;
use Platform\Billing\BillingServiceProvider;

abstract class TestCase extends OrchestraTestCase
{
    /**
     * @param  \Illuminate\Foundation\Application  $app
     * @return array<int, class-string>
     */
    protected function getPackageProviders($app): array
    {
        return [BillingServiceProvider::class];
    }

    protected function defineDatabaseMigrations(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
    }
}
