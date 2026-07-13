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
        $this->app->singleton(Gateways\FlutterwavePaymentGateway::class);
        $this->app->singleton(Services\PaymentGatewayResolver::class);
        $this->app->singleton(Services\TenantPaymentProviderService::class);
        $this->app->singleton(Services\TenantPaymentCredentialsService::class);
        $this->app->singleton(Services\PaymentConnectorFactory::class);
        $this->app->singleton(Services\WebhookSignatureResolver::class);
        $this->app->bind(PaymentGatewayInterface::class, PaystackPaymentGateway::class);
        $this->app->singleton(PaymentOrchestrator::class);
        $this->app->singleton(Services\WebhookEventRecorder::class);
        $this->app->singleton(Services\RefundService::class);
        $this->app->singleton(Services\DisputeService::class);
        $this->app->singleton(Services\DisputeDeadlineAlertService::class);
        $this->app->singleton(Services\DisputeDeadlineNotifier::class);
        $this->app->singleton(Services\NightlyReconciliationService::class);
        $this->app->singleton(Services\PaymentReconciliationService::class);
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');

        if ($this->app->runningInConsole()) {
            $this->commands([
                Console\ReconcilePendingPaymentsCommand::class,
                Console\NightlyReconciliationCommand::class,
                Console\AlertDisputeDeadlinesCommand::class,
            ]);
        }

        Route::prefix('api')
            ->middleware('api')
            ->group(__DIR__.'/../routes/api.php');
    }
}
