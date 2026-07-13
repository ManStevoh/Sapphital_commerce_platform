<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Commerce\Checkout\Http\Controllers\CheckoutController;
use Modules\Commerce\Checkout\Http\Controllers\HealthController;

Route::get('/v1/commerce/checkout/health', [HealthController::class, 'show'])
    ->name('checkout.health.show');

Route::middleware('tenant.context')->group(function (): void {
    Route::post('/v1/commerce/checkout/sessions', [CheckoutController::class, 'store'])
        ->middleware(['turnstile.verify', 'idempotency', 'throttle:20,1'])
        ->name('checkout.sessions.store');

    Route::patch('/v1/commerce/checkout/sessions/{id}', [CheckoutController::class, 'update'])
        ->middleware('throttle:20,1')
        ->name('checkout.sessions.update');
});
