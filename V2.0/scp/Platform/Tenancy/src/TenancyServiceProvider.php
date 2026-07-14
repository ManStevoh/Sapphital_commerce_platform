<?php

declare(strict_types=1);

namespace Platform\Tenancy;

use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Platform\Tenancy\Console\GenerateIsolationTestsCommand;
use Platform\Tenancy\Console\VerifyCustomDomainsCommand;
use Platform\Tenancy\Contracts\CustomHostnameSslProvisioner;
use Platform\Tenancy\Contracts\DomainDnsVerifier;
use Platform\Tenancy\Middleware\SetTenantContext;
use Platform\Tenancy\Services\CustomDomainService;
use Platform\Tenancy\Services\FakeCustomHostnameSslProvisioner;
use Platform\Tenancy\Services\FakeDomainDnsVerifier;
use Platform\Tenancy\Services\PhpDomainDnsVerifier;

final class TenancyServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/domains.php', 'domains');

        $this->app->singleton(DomainDnsVerifier::class, function () {
            if (app()->environment('testing') || (string) config('domains.ssl_provider', 'fake') === 'fake') {
                return new FakeDomainDnsVerifier;
            }

            return new PhpDomainDnsVerifier;
        });

        $this->app->singleton(CustomHostnameSslProvisioner::class, FakeCustomHostnameSslProvisioner::class);
        $this->app->singleton(CustomDomainService::class);
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');

        if ($this->app->runningInConsole()) {
            $this->commands([
                GenerateIsolationTestsCommand::class,
                VerifyCustomDomainsCommand::class,
            ]);
        }

        /** @var Router $router */
        $router = $this->app->make(Router::class);
        $router->aliasMiddleware('tenant.context', SetTenantContext::class);
        $router->aliasMiddleware('idempotency', Middleware\EnsureIdempotency::class);

        Route::prefix('api')
            ->middleware('api')
            ->group(__DIR__.'/../routes/api.php');
    }
}
