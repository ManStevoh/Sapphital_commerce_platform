<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Platform\Identity\Http\Controllers\HealthController;
use Platform\Identity\Http\Controllers\MeController;
use Platform\Identity\Http\Controllers\MerchantAuthController;
use Platform\Identity\Http\Controllers\PlatformAuthController;

Route::get('/v1/platform/identity/health', [HealthController::class, 'show'])
    ->name('identity.health.show');

Route::prefix('v1/auth')->group(function (): void {
    Route::post('/merchant/login', [MerchantAuthController::class, 'login'])
        ->middleware('throttle:5,1')
        ->name('identity.auth.merchant.login');

    Route::post('/platform/login', [PlatformAuthController::class, 'login'])
        ->middleware('throttle:5,1')
        ->name('identity.auth.platform.login');

    Route::get('/me', [MeController::class, 'show'])
        ->middleware('auth:sanctum')
        ->name('identity.auth.me');
});
