<?php

declare(strict_types=1);

namespace Platform\Secrets;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Platform\Secrets\Contracts\SecretVaultInterface;
use Platform\Secrets\Drivers\FileSecretVault;

final class SecretsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/secrets.php', 'secrets');

        $this->app->singleton(SecretVaultInterface::class, function (): SecretVaultInterface {
            $path = config('secrets.paths.default');

            if (! is_string($path) || $path === '') {
                throw new \RuntimeException('Secrets file path is not configured.');
            }

            return new FileSecretVault($path);
        });
    }

    public function boot(): void
    {
        Route::prefix('api')
            ->middleware('api')
            ->group(__DIR__.'/../routes/api.php');
    }
}
