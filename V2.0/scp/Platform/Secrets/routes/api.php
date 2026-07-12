<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Platform\Secrets\Http\Controllers\HealthController;

Route::get('/v1/platform/secrets/health', [HealthController::class, 'show'])
    ->name('secrets.health.show');
