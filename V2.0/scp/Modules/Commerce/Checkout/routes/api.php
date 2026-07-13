<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Commerce\Checkout\Http\Controllers\CheckoutController;
use Modules\Commerce\Checkout\Http\Controllers\GiftCardController;
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

    Route::post('/v1/commerce/checkout/sessions/{id}/gift-card', [GiftCardController::class, 'apply'])
        ->middleware('throttle:20,1')
        ->name('checkout.sessions.gift-card.apply');

    Route::middleware(['auth:sanctum', 'merchant.tenant', 'permission.check:catalog.write'])->group(function (): void {
        Route::post('/v1/commerce/gift-cards', [GiftCardController::class, 'store'])
            ->name('gift-cards.store');

        Route::get('/v1/commerce/gift-cards/by-code/{code}', [GiftCardController::class, 'showByCode'])
            ->name('gift-cards.show-by-code');

        Route::post('/v1/commerce/gift-cards/{id}/disable', [GiftCardController::class, 'disable'])
            ->name('gift-cards.disable');
    });
});
