<?php

declare(strict_types=1);

namespace Modules\Commerce\Orders;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Modules\Commerce\Orders\Services\DigitalFulfillmentService;
use Modules\Commerce\Orders\Services\InventoryRestockService;
use Modules\Commerce\Orders\Services\OrderService;
use Modules\Commerce\Orders\Services\ReturnRequestService;
use Modules\Commerce\Orders\Services\ReturnWindowService;
use Modules\Commerce\Orders\Services\StoreSettingsService;

final class OrdersServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(OrderService::class);
        $this->app->singleton(ReturnRequestService::class);
        $this->app->singleton(ReturnWindowService::class);
        $this->app->singleton(StoreSettingsService::class);
        $this->app->singleton(InventoryRestockService::class);
        $this->app->singleton(DigitalFulfillmentService::class);
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');

        Route::prefix('api')
            ->middleware('api')
            ->group(__DIR__.'/../routes/api.php');
    }
}
