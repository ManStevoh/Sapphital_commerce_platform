<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Commerce\Orders\Http\Controllers\HealthController;
use Modules\Commerce\Orders\Http\Controllers\OrderController;

Route::get('/v1/commerce/orders/health', [HealthController::class, 'show'])
    ->name('orders.health.show');

Route::middleware('tenant.context')->group(function (): void {
    Route::get('/v1/commerce/orders/{id}', [OrderController::class, 'show'])
        ->name('orders.show');

    Route::post('/v1/commerce/orders/from-checkout', [OrderController::class, 'createFromCheckout'])
        ->name('orders.from-checkout');

    Route::middleware(['auth:sanctum', 'merchant.tenant'])->group(function (): void {
        Route::get('/v1/commerce/orders', [OrderController::class, 'index'])
            ->name('orders.index');
    });
});
