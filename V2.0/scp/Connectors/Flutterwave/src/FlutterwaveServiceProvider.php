<?php

declare(strict_types=1);

namespace Connectors\Flutterwave;

use Illuminate\Support\ServiceProvider;

final class FlutterwaveServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/flutterwave.php', 'flutterwave');

        $this->app->singleton(FlutterwaveConnectorInterface::class, FlutterwaveConnector::class);
    }

    public function boot(): void
    {
        //
    }
}
