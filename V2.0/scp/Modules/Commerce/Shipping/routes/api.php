<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Commerce\Shipping\Http\Controllers\HealthController;
use Modules\Commerce\Shipping\Http\Controllers\ShipmentController;
use Modules\Commerce\Shipping\Http\Controllers\ShippingRateController;

Route::get('/v1/commerce/shipping/health', [HealthController::class, 'show'])
    ->name('shipping.health.show');

Route::middleware('tenant.context')->group(function (): void {
    Route::get('/v1/commerce/shipping/rates', [ShippingRateController::class, 'index'])
        ->name('shipping.rates.index');

    Route::get('/v1/commerce/shipping/shipments', [ShipmentController::class, 'index'])
        ->name('shipping.shipments.index');

    Route::get('/v1/commerce/shipping/shipments/{id}', [ShipmentController::class, 'show'])
        ->name('shipping.shipments.show');

    Route::middleware(['auth:sanctum', 'merchant.tenant', 'permission.check:shipments.manage'])->group(function (): void {
        Route::post('/v1/commerce/shipping/shipments/from-order', [ShipmentController::class, 'createFromOrder'])
            ->name('shipping.shipments.from-order');

        Route::patch('/v1/commerce/shipping/shipments/{id}/ship', [ShipmentController::class, 'ship'])
            ->name('shipping.shipments.ship');

        Route::patch('/v1/commerce/shipping/shipments/{id}/deliver', [ShipmentController::class, 'deliver'])
            ->name('shipping.shipments.deliver');
    });
});
