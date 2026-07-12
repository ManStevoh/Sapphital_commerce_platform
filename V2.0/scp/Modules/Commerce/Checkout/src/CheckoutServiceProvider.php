<?php

declare(strict_types=1);

namespace Modules\Commerce\Checkout;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Modules\Commerce\Checkout\Services\CheckoutService;

final class CheckoutServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(CheckoutService::class);
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');

        Route::prefix('api')
            ->middleware('api')
            ->group(__DIR__.'/../routes/api.php');
    }
}
