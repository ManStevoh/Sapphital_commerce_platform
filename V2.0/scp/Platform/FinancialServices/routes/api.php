<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Platform\FinancialServices\Http\Controllers\DisputeController;
use Platform\FinancialServices\Http\Controllers\FlutterwaveWebhookController;
use Platform\FinancialServices\Http\Controllers\HealthController;
use Platform\FinancialServices\Http\Controllers\PaymentController;
use Platform\FinancialServices\Http\Controllers\PaymentReconciliationController;
use Platform\FinancialServices\Http\Controllers\WebhookController;

Route::get('/v1/platform/financial-services/health', [HealthController::class, 'show'])
    ->name('financial-services.health.show');

Route::post('/v1/webhooks/paystack', WebhookController::class)
    ->name('webhooks.paystack');

Route::post('/v1/webhooks/flutterwave', FlutterwaveWebhookController::class)
    ->name('webhooks.flutterwave');

Route::middleware('tenant.context')->group(function (): void {
    Route::post('/v1/platform/financial-services/payments/initialize', [PaymentController::class, 'initialize'])
        ->middleware(['idempotency', 'throttle:10,1'])
        ->name('financial-services.payments.initialize');

    Route::post('/v1/platform/financial-services/payments/verify', [PaymentController::class, 'verify'])
        ->middleware(['idempotency', 'throttle:10,1'])
        ->name('financial-services.payments.verify');

    Route::middleware(['auth:sanctum', 'merchant.tenant', 'permission.check:payments.read'])->group(function (): void {
        Route::get('/v1/platform/financial-services/reconciliation', [PaymentReconciliationController::class, 'index'])
            ->name('financial-services.reconciliation.index');

        Route::get('/v1/platform/financial-services/reconciliation/export', [PaymentReconciliationController::class, 'export'])
            ->name('financial-services.reconciliation.export');
    });

    Route::middleware(['auth:sanctum', 'merchant.tenant', 'permission.check:disputes.manage'])->group(function (): void {
        Route::get('/v1/platform/financial-services/disputes', [DisputeController::class, 'index'])
            ->name('financial-services.disputes.index');

        Route::post('/v1/platform/financial-services/disputes/{id}/resolve', [DisputeController::class, 'resolve'])
            ->name('financial-services.disputes.resolve');
    });
});
