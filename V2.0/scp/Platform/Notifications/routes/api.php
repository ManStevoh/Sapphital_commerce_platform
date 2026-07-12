<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Platform\Notifications\Http\Controllers\HealthController;

Route::get('/v1/platform/notifications/health', [HealthController::class, 'show'])
    ->name('notifications.health.show');
