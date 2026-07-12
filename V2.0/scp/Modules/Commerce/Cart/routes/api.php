<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Commerce\Cart\Http\Controllers\CartController;
use Modules\Commerce\Cart\Http\Controllers\HealthController;

Route::get('/v1/commerce/cart/health', [HealthController::class, 'show'])
    ->name('cart.health.show');

Route::middleware('tenant.context')->group(function (): void {
    Route::get('/v1/commerce/cart', [CartController::class, 'show'])
        ->name('cart.show');

    Route::post('/v1/commerce/cart/items', [CartController::class, 'addItem'])
        ->name('cart.items.store');
});
