<?php

declare(strict_types=1);

namespace Platform\Identity;

use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Platform\Identity\Middleware\CheckPermission;
use Platform\Identity\Middleware\EnsureMerchantTenant;
use Platform\Identity\Middleware\EnsurePlatformAdmin;

final class IdentityServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/identity.php', 'identity');

        $this->app->singleton(Contracts\BotVerifier::class, function (): Contracts\BotVerifier {
            if (! config('turnstile.enabled')) {
                return new BotVerification\NullBotVerifier;
            }

            return new BotVerification\TurnstileBotVerifier;
        });

        $this->app->singleton(Services\MerchantPermissionResolver::class);
        $this->app->singleton(Services\SignupHandoffService::class);
        $this->app->singleton(Services\TotpService::class);
        $this->app->singleton(Services\PlatformMfaService::class);
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');

        /** @var Router $router */
        $router = $this->app->make(Router::class);
        $router->aliasMiddleware('permission.check', CheckPermission::class);
        $router->aliasMiddleware('merchant.tenant', EnsureMerchantTenant::class);
        $router->aliasMiddleware('platform.admin', EnsurePlatformAdmin::class);
        $router->aliasMiddleware('turnstile.verify', Middleware\VerifyTurnstile::class);

        Route::prefix('api')
            ->middleware('api')
            ->group(__DIR__.'/../routes/api.php');
    }
}
