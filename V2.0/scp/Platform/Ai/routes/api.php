<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Platform\Ai\Http\Controllers\AiController;

Route::middleware('tenant.context')->group(function (): void {
    Route::middleware(['auth:sanctum', 'merchant.tenant', 'permission.check:catalog.write'])->group(function (): void {
        Route::post('/v1/platform/ai/product-description', [AiController::class, 'generateProductDescription'])
            ->name('ai.product-description.generate');

        Route::post('/v1/platform/ai/seo-meta', [AiController::class, 'generateSeoMeta'])
            ->name('ai.seo-meta.generate');

        Route::get('/v1/platform/ai/usage', [AiController::class, 'usage'])
            ->name('ai.usage.show');

        Route::put('/v1/platform/ai/settings', [AiController::class, 'updateSettings'])
            ->name('ai.settings.update');
    });
});
