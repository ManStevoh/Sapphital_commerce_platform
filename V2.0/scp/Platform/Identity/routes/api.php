<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Platform\Identity\Http\Controllers\HealthController;
use Platform\Identity\Http\Controllers\MeController;
use Platform\Identity\Http\Controllers\MerchantAuthController;
use Platform\Identity\Http\Controllers\PlatformAuthController;
use Platform\Identity\Http\Controllers\PlatformMfaController;

Route::get('/v1/platform/identity/health', [HealthController::class, 'show'])
    ->name('identity.health.show');

Route::prefix('v1/auth')->group(function (): void {
    Route::post('/merchant/login', [MerchantAuthController::class, 'login'])
        ->middleware(['turnstile.verify', 'throttle:5,1'])
        ->name('identity.auth.merchant.login');

    Route::post('/merchant/handoff', [MerchantAuthController::class, 'handoff'])
        ->middleware(['throttle:10,1'])
        ->name('identity.auth.merchant.handoff');

    Route::post('/platform/login', [PlatformAuthController::class, 'login'])
        ->middleware(['turnstile.verify', 'throttle:5,1'])
        ->name('identity.auth.platform.login');

    Route::prefix('platform/mfa')->group(function (): void {
        Route::post('/setup', [PlatformMfaController::class, 'setup'])
            ->middleware(['throttle:10,1'])
            ->name('identity.auth.platform.mfa.setup');

        Route::post('/confirm', [PlatformMfaController::class, 'confirm'])
            ->middleware(['throttle:10,1'])
            ->name('identity.auth.platform.mfa.confirm');

        Route::post('/verify', [PlatformMfaController::class, 'verify'])
            ->middleware(['throttle:10,1'])
            ->name('identity.auth.platform.mfa.verify');
    });

    Route::get('/me', [MeController::class, 'show'])
        ->middleware('auth:sanctum')
        ->name('identity.auth.me');
});
