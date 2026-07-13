<?php

declare(strict_types=1);

namespace Platform\Tenancy;

use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Platform\Tenancy\Middleware\SetTenantContext;

final class TenancyServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');

        if ($this->app->runningInConsole()) {
            $this->commands([
                Console\GenerateIsolationTestsCommand::class,
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