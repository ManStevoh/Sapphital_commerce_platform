<?php

declare(strict_types=1);

namespace Platform\Provisioning;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Platform\Provisioning\Services\ProvisionTenantService;
use Platform\Provisioning\Services\SignupService;

final class ProvisioningServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(ProvisionTenantService::class);
        $this->app->singleton(SignupService::class);
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');

        Route::prefix('api')
            ->middleware('api')
            ->group(__DIR__.'/../routes/api.php');
    }
}
