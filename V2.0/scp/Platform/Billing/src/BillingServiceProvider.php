<?php

declare(strict_types=1);

namespace Platform\Billing;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Platform\Billing\Console\ProcessExpiredTrialsCommand;
use Platform\Billing\Console\SuspendOverdueSubscriptionsCommand;
use Platform\Billing\Contracts\TenantProductCounter;
use Platform\Billing\Services\EntitlementChecker;
use Platform\Billing\Services\NullTenantProductCounter;
use Platform\Billing\Services\SubscriptionLifecycleService;

final class BillingServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/billing.php', 'billing');

        $this->app->singleton(EntitlementChecker::class);
        $this->app->singleton(SubscriptionLifecycleService::class);
        $this->app->singleton(Services\BillingSubscriptionPaymentService::class);
        $this->app->singleton(Services\BillingTaxService::class);
        $this->app->singleton(Services\InvoicePdfService::class);
        $this->app->singleton(Services\TenantBillingSettingsService::class);
        $this->app->singleton(Support\SimplePdfBuilder::class);
    }

    public function boot(): void
    {
        if (! $this->app->bound(TenantProductCounter::class)) {
            $this->app->singleton(TenantProductCounter::class, NullTenantProductCounter::class);
        }

        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');

        if ($this->app->runningInConsole()) {
            $this->commands([
                ProcessExpiredTrialsCommand::class,
                SuspendOverdueSubscriptionsCommand::class,
            ]);
        }

        Route::prefix('api')
            ->middleware('api')
            ->group(__DIR__.'/../routes/api.php');
    }
}
