<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Commerce\Catalog\Http\Controllers\DigitalAssetController;
use Modules\Commerce\Catalog\Http\Controllers\CollectionController;
use Modules\Commerce\Catalog\Http\Controllers\HealthController;
use Modules\Commerce\Catalog\Http\Controllers\ProductController;
use Modules\Commerce\Catalog\Http\Controllers\SearchController;
use Modules\Commerce\Catalog\Http\Controllers\StorefrontController;

Route::get('/v1/commerce/catalog/health', [HealthController::class, 'show'])
    ->name('catalog.health.show');

Route::get('/v1/commerce/storefront/themes', [StorefrontController::class, 'themes'])
    ->name('storefront.themes.index');

Route::middleware('tenant.context')->group(function (): void {
    Route::get('/v1/commerce/storefront/theme', [StorefrontController::class, 'theme'])
        ->name('storefront.theme.show');

    Route::get('/v1/commerce/storefront/themes/{themeId}/preview', [StorefrontController::class, 'preview'])
        ->name('storefront.themes.preview');

    Route::get('/v1/commerce/catalog/products', [ProductController::class, 'index'])
        ->name('catalog.products.index');

    Route::get('/v1/commerce/catalog/products/{id}', [ProductController::class, 'show'])
        ->name('catalog.products.show');

    Route::get('/v1/commerce/catalog/products/{id}/related', [ProductController::class, 'related'])
        ->name('catalog.products.related');

    Route::get('/v1/commerce/catalog/collections', [CollectionController::class, 'index'])
        ->name('catalog.collections.index');

    Route::get('/v1/commerce/catalog/collections/published', [CollectionController::class, 'published'])
        ->name('catalog.collections.published');

    Route::get('/v1/commerce/catalog/collections/by-slug/{slug}', [CollectionController::class, 'bySlug'])
        ->name('catalog.collections.by-slug');

    Route::get('/v1/commerce/catalog/collections/{id}', [CollectionController::class, 'show'])
        ->name('catalog.collections.show');

    Route::get('/v1/commerce/catalog/collections/{id}/products', [CollectionController::class, 'products'])
        ->name('catalog.collections.products');

    Route::get('/v1/commerce/catalog/search', [SearchController::class, 'search'])
        ->name('catalog.search');

    Route::get('/v1/commerce/catalog/search/autocomplete', [SearchController::class, 'autocomplete'])
        ->middleware('throttle:60,1')
        ->name('catalog.search.autocomplete');

    Route::middleware(['auth:sanctum', 'merchant.tenant', 'permission.check:catalog.write'])->group(function (): void {
        Route::post('/v1/commerce/catalog/products', [ProductController::class, 'store'])
            ->name('catalog.products.store');

        Route::put('/v1/commerce/catalog/products/{id}', [ProductController::class, 'update'])
            ->name('catalog.products.update');

        Route::delete('/v1/commerce/catalog/products/{id}', [ProductController::class, 'destroy'])
            ->name('catalog.products.destroy');

        Route::get('/v1/commerce/catalog/products/{id}/digital-asset', [DigitalAssetController::class, 'show'])
            ->name('catalog.products.digital-asset.show');

        Route::post('/v1/commerce/catalog/products/{id}/digital-asset', [DigitalAssetController::class, 'store'])
            ->name('catalog.products.digital-asset.store');

        Route::post('/v1/commerce/catalog/collections', [CollectionController::class, 'store'])
            ->name('catalog.collections.store');

        Route::put('/v1/commerce/catalog/collections/{id}', [CollectionController::class, 'update'])
            ->name('catalog.collections.update');

        Route::delete('/v1/commerce/catalog/collections/{id}', [CollectionController::class, 'destroy'])
            ->name('catalog.collections.destroy');

        Route::put('/v1/commerce/catalog/collections/{id}/products', [CollectionController::class, 'syncProducts'])
            ->name('catalog.collections.products.sync');

        Route::get('/v1/commerce/catalog/search/analytics', [SearchController::class, 'analytics'])
            ->name('catalog.search.analytics');

        Route::get('/v1/commerce/catalog/search/synonyms', [SearchController::class, 'synonymsIndex'])
            ->name('catalog.search.synonyms.index');

        Route::post('/v1/commerce/catalog/search/synonyms', [SearchController::class, 'synonymsStore'])
            ->name('catalog.search.synonyms.store');

        Route::delete('/v1/commerce/catalog/search/synonyms/{id}', [SearchController::class, 'synonymsDestroy'])
            ->name('catalog.search.synonyms.destroy');

        Route::put('/v1/commerce/storefront/theme/settings', [StorefrontController::class, 'updateThemeSettings'])
            ->name('storefront.theme.settings.update');

        Route::put('/v1/commerce/storefront/theme', [StorefrontController::class, 'applyTheme'])
            ->name('storefront.theme.apply');
    });
});
