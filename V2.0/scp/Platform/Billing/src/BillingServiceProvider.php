<?php

declare(strict_types=1);

namespace Platform\Billing;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Platform\Billing\Contracts\TenantProductCounter;
use Platform\Billing\Services\EntitlementChecker;
use Platform\Billing\Services\NullTenantProductCounter;

final class BillingServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(EntitlementChecker::class);
    }

    public function boot(): void
    {
        if (! $this->app->bound(TenantProductCounter::class)) {
            $this->app->singleton(TenantProductCounter::class, NullTenantProductCounter::class);
        }

        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');

        Route::prefix('api')
            ->middleware('api')
            ->group(__DIR__.'/../routes/api.php');
    }
}
