<?php

declare(strict_types=1);

namespace Modules\Commerce\Catalog;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Modules\Commerce\Catalog\Services\CatalogProductCounter;
use Modules\Commerce\Catalog\Services\ThemeResolver;
use Platform\Billing\Contracts\TenantProductCounter;
use Platform\Billing\Services\EntitlementChecker;

final class CatalogServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        if (! $this->app->bound(TenantProductCounter::class)) {
            $this->app->singleton(TenantProductCounter::class, CatalogProductCounter::class);
        }

        $this->app->singleton(EntitlementChecker::class);
        $this->app->singleton(ThemeResolver::class);
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');

        $this->app->singleton(TenantProductCounter::class, CatalogProductCounter::class);

        Route::prefix('api')
            ->middleware('api')
            ->group(__DIR__.'/../routes/api.php');
    }
}
