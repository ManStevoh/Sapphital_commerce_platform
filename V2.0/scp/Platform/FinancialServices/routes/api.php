<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Platform\FinancialServices\Http\Controllers\HealthController;
use Platform\FinancialServices\Http\Controllers\PaymentController;
use Platform\FinancialServices\Http\Controllers\WebhookController;

Route::get('/v1/platform/financial-services/health', [HealthController::class, 'show'])
    ->name('financial-services.health.show');

Route::post('/v1/webhooks/paystack', WebhookController::class)
    ->name('webhooks.paystack');

Route::middleware('tenant.context')->group(function (): void {
    Route::post('/v1/platform/financial-services/payments/initialize', [PaymentController::class, 'initialize'])
        ->name('financial-services.payments.initialize');

    Route::post('/v1/platform/financial-services/payments/verify', [PaymentController::class, 'verify'])
        ->name('financial-services.payments.verify');
});
