<?php

declare(strict_types=1);

namespace Platform\Notifications;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

final class NotificationsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(Services\OrderConfirmationNotifier::class);
        $this->app->singleton(Services\RefundConfirmationNotifier::class);
    }

    public function boot(): void
    {
        Route::prefix('api')
            ->middleware('api')
            ->group(__DIR__.'/../routes/api.php');
    }
}
