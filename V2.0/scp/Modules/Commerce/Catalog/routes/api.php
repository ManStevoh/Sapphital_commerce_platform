<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Commerce\Catalog\Http\Controllers\HealthController;
use Modules\Commerce\Catalog\Http\Controllers\ProductController;
use Modules\Commerce\Catalog\Http\Controllers\StorefrontController;

Route::get('/v1/commerce/catalog/health', [HealthController::class, 'show'])
    ->name('catalog.health.show');

Route::get('/v1/commerce/storefront/themes', [StorefrontController::class, 'themes'])
    ->name('storefront.themes.index');

Route::middleware('tenant.context')->group(function (): void {
    Route::get('/v1/commerce/storefront/theme', [StorefrontController::class, 'theme'])
        ->name('storefront.theme.show');

    Route::get('/v1/commerce/catalog/products', [ProductController::class, 'index'])
        ->name('catalog.products.index');

    Route::get('/v1/commerce/catalog/products/{id}', [ProductController::class, 'show'])
        ->name('catalog.products.show');

    Route::middleware(['auth:sanctum', 'merchant.tenant'])->group(function (): void {
        Route::post('/v1/commerce/catalog/products', [ProductController::class, 'store'])
            ->name('catalog.products.store');

        Route::put('/v1/commerce/catalog/products/{id}', [ProductController::class, 'update'])
            ->name('catalog.products.update');

        Route::delete('/v1/commerce/catalog/products/{id}', [ProductController::class, 'destroy'])
            ->name('catalog.products.destroy');

        Route::put('/v1/commerce/storefront/theme/settings', [StorefrontController::class, 'updateThemeSettings'])
            ->name('storefront.theme.settings.update');
    });
});
