<?php

declare(strict_types=1);

use Platform\Identity\Http\Controllers\CustomerAccountController;
use Platform\Identity\Http\Controllers\CustomerAuthController;
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

    Route::middleware('tenant.context')->group(function (): void {
        Route::post('/customer/register', [CustomerAuthController::class, 'register'])
            ->middleware(['throttle:10,1'])
            ->name('identity.auth.customer.register');

        Route::post('/customer/login', [CustomerAuthController::class, 'login'])
            ->middleware(['throttle:10,1'])
            ->name('identity.auth.customer.login');
    });

    Route::post('/customer/logout', [CustomerAuthController::class, 'logout'])
        ->middleware(['auth:sanctum', 'throttle:30,1'])
        ->name('identity.auth.customer.logout');

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

Route::middleware(['auth:sanctum', 'tenant.context'])->prefix('v1/commerce/account')->group(function (): void {
    Route::get('/orders', [CustomerAccountController::class, 'orders'])
        ->name('account.orders.index');

    Route::get('/addresses', [CustomerAccountController::class, 'addressesIndex'])
        ->name('account.addresses.index');

    Route::post('/addresses', [CustomerAccountController::class, 'addressesStore'])
        ->name('account.addresses.store');

    Route::delete('/addresses/{id}', [CustomerAccountController::class, 'addressesDestroy'])
        ->name('account.addresses.destroy');
});
