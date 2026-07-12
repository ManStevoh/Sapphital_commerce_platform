<?php

declare(strict_types=1);

namespace Modules\Commerce\Shipping;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Modules\Commerce\Shipping\Services\DefaultShippingSetup;
use Modules\Commerce\Shipping\Services\ShipmentService;
use Modules\Commerce\Shipping\Services\ShippingRateCalculator;

final class ShippingServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(DefaultShippingSetup::class);
        $this->app->singleton(ShippingRateCalculator::class);
        $this->app->singleton(ShipmentService::class);
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');

        Route::prefix('api')
            ->middleware('api')
            ->group(__DIR__.'/../routes/api.php');
    }
}
