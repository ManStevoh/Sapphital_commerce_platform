<?php

declare(strict_types=1);

use Platform\Messaging\Http\Controllers\WebhookEndpointController;

Route::get('/v1/platform/messaging/health', static function () {
    return response()->json([
        'status' => 'ok',
        'package' => 'messaging',
    ]);
})->name('messaging.health.show');

Route::middleware(['tenant.context', 'auth:sanctum', 'merchant.tenant', 'permission.check:catalog.write'])
    ->prefix('v1/commerce/webhook-endpoints')
    ->group(function (): void {
        Route::get('/', [WebhookEndpointController::class, 'index'])
            ->name('messaging.webhook-endpoints.index');

        Route::post('/', [WebhookEndpointController::class, 'store'])
            ->name('messaging.webhook-endpoints.store');

        Route::delete('/{id}', [WebhookEndpointController::class, 'destroy'])
            ->name('messaging.webhook-endpoints.destroy');
    });
