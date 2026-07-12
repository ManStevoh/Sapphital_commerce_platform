<?php

declare(strict_types=1);

namespace Platform\FinancialServices;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Platform\FinancialServices\Contracts\PaymentGatewayInterface;
use Platform\FinancialServices\Gateways\PaystackPaymentGateway;
use Platform\FinancialServices\Services\PaymentOrchestrator;

final class FinancialServicesServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(PaystackPaymentGateway::class);
        $this->app->bind(PaymentGatewayInterface::class, PaystackPaymentGateway::class);
        $this->app->singleton(PaymentOrchestrator::class);
    }

    public function boot(): void
    {
        Route::prefix('api')
            ->middleware('api')
            ->group(__DIR__.'/../routes/api.php');
    }
}
