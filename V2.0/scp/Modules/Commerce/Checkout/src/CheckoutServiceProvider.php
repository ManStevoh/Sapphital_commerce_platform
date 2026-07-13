<?php

declare(strict_types=1);

namespace Modules\Commerce\Checkout;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Modules\Commerce\Checkout\Console\ExpireGiftCardsCommand;
use Modules\Commerce\Checkout\Services\CheckoutService;
use Modules\Commerce\Checkout\Services\GiftCardService;

final class CheckoutServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(CheckoutService::class);
        $this->app->singleton(GiftCardService::class);
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');

        if ($this->app->runningInConsole()) {
            $this->commands([
                ExpireGiftCardsCommand::class,
            ]);
        }

        Route::prefix('api')
            ->middleware('api')
            ->group(__DIR__.'/../routes/api.php');
    }
}
