<?php

declare(strict_types=1);

namespace Connectors\Paystack;

use Illuminate\Support\ServiceProvider;

final class PaystackServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/paystack.php', 'paystack');

        $this->app->singleton(PaystackConnectorInterface::class, PaystackConnector::class);
    }

    public function boot(): void
    {
        //
    }
}
