<?php

declare(strict_types=1);

namespace Modules\Content\Cms;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

final class CmsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');

        Route::prefix('api')
            ->middleware('api')
            ->group(__DIR__.'/../routes/api.php');
    }
}
