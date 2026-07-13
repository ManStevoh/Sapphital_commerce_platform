<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Commerce\Orders\Http\Controllers\DigitalDownloadController;
use Modules\Commerce\Orders\Http\Controllers\HealthController;
use Modules\Commerce\Orders\Http\Controllers\OrderController;
use Modules\Commerce\Orders\Http\Controllers\ReturnRequestController;
use Modules\Commerce\Orders\Http\Controllers\StoreSettingsController;

Route::get('/v1/commerce/orders/health', [HealthController::class, 'show'])
    ->name('orders.health.show');

Route::get('/v1/commerce/orders/digital-downloads/file/{tenantId}/{orderItemId}', [DigitalDownloadController::class, 'file'])
    ->middleware('signed')
    ->name('orders.digital-downloads.file');

Route::middleware('tenant.context')->group(function (): void {
    Route::get('/v1/commerce/orders/{id}', [OrderController::class, 'show'])
        ->name('orders.show');

    Route::post('/v1/commerce/orders/from-checkout', [OrderController::class, 'createFromCheckout'])
        ->name('orders.from-checkout');

    Route::post('/v1/commerce/returns/guest/lookup', [ReturnRequestController::class, 'lookupGuest'])
        ->middleware('throttle:10,1')
        ->name('returns.guest.lookup');

    Route::post('/v1/commerce/returns/guest', [ReturnRequestController::class, 'storeGuest'])
        ->middleware('throttle:10,1')
        ->name('returns.guest.store');

    Route::get('/v1/commerce/storefront/checkout-settings', [StoreSettingsController::class, 'checkoutSettings'])
        ->name('storefront.checkout-settings.show');

    Route::post('/v1/commerce/orders/digital-downloads', [DigitalDownloadController::class, 'store'])
        ->middleware('throttle:20,1')
        ->name('orders.digital-downloads.store');

    Route::middleware(['auth:sanctum', 'merchant.tenant', 'permission.check:orders.read'])->group(function (): void {
        Route::get('/v1/commerce/orders', [OrderController::class, 'index'])
            ->name('orders.index');
    });

    Route::middleware(['auth:sanctum', 'merchant.tenant', 'permission.check:orders.refund', 'idempotency'])
        ->post('/v1/commerce/orders/{id}/refund', [OrderController::class, 'refund'])
        ->name('orders.refund');

    Route::middleware(['auth:sanctum', 'merchant.tenant', 'permission.check:returns.manage'])->group(function (): void {
        Route::put('/v1/commerce/storefront/settings/returns', [StoreSettingsController::class, 'updateReturns'])
            ->name('storefront.settings.returns.update');
    });

    Route::middleware(['auth:sanctum', 'merchant.tenant', 'permission.check:payments.write'])->group(function (): void {
        Route::get('/v1/commerce/storefront/settings/payments', [StoreSettingsController::class, 'showPayments'])
            ->name('storefront.settings.payments.show');

        Route::put('/v1/commerce/storefront/settings/payments', [StoreSettingsController::class, 'updatePayments'])
            ->name('storefront.settings.payments.update');

        Route::get('/v1/commerce/storefront/settings/payments/credentials', [StoreSettingsController::class, 'showPaymentCredentials'])
            ->name('storefront.settings.payments.credentials.show');

        Route::put('/v1/commerce/storefront/settings/payments/credentials', [StoreSettingsController::class, 'updatePaymentCredentials'])
            ->name('storefront.settings.payments.credentials.update');
    });

    Route::middleware(['auth:sanctum', 'merchant.tenant', 'permission.check:returns.manage'])->group(function (): void {
        Route::get('/v1/commerce/storefront/settings', [StoreSettingsController::class, 'show'])
            ->name('storefront.settings.show');

        Route::get('/v1/commerce/returns', [ReturnRequestController::class, 'index'])
            ->name('returns.index');

        Route::post('/v1/commerce/returns', [ReturnRequestController::class, 'store'])
            ->name('returns.store');

        Route::post('/v1/commerce/returns/{id}/approve', [ReturnRequestController::class, 'approve'])
            ->middleware('idempotency')
            ->name('returns.approve');

        Route::post('/v1/commerce/returns/{id}/ship', [ReturnRequestController::class, 'ship'])
            ->name('returns.ship');

        Route::post('/v1/commerce/returns/{id}/receive', [ReturnRequestController::class, 'receive'])
            ->middleware('idempotency')
            ->name('returns.receive');

        Route::post('/v1/commerce/returns/{id}/reject', [ReturnRequestController::class, 'reject'])
            ->name('returns.reject');
    });
});
