<?php

declare(strict_types=1);

namespace Platform\Messaging;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Platform\Messaging\Console\PollOutboxCommand;
use Platform\Messaging\Services\OutboxPoller;
use Platform\Messaging\Services\OutboxWriter;
use Platform\Messaging\Services\WebhookDispatcher;
use Platform\Messaging\Services\WebhookSigner;
use Platform\Messaging\Services\WebhookUrlGuard;

final class MessagingServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(OutboxWriter::class);
        $this->app->singleton(WebhookSigner::class);
        $this->app->singleton(WebhookUrlGuard::class);
        $this->app->singleton(WebhookDispatcher::class);
        $this->app->singleton(OutboxPoller::class);
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');

        if ($this->app->runningInConsole()) {
            $this->commands([
                PollOutboxCommand::class,
            ]);
        }

        Route::prefix('api')
            ->middleware('api')
            ->group(__DIR__.'/../routes/api.php');
    }
}
