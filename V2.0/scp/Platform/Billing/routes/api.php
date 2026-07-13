<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Platform\Billing\Http\Controllers\ActivateSubscriptionController;
use Platform\Billing\Http\Controllers\HealthController;
use Platform\Billing\Http\Controllers\InitializeSubscriptionPaymentController;
use Platform\Billing\Http\Controllers\InvoicePdfController;
use Platform\Billing\Http\Controllers\MerchantBillingController;
use Platform\Billing\Http\Controllers\PlanController;
use Platform\Billing\Http\Controllers\SubscriptionController;
use Platform\Billing\Http\Controllers\TenantBillingSettingsController;

Route::get('/v1/platform/billing/health', [HealthController::class, 'show'])
    ->name('billing.health.show');

Route::get('/v1/platform/billing/plans', [PlanController::class, 'index'])
    ->name('billing.plans.index');

Route::get('/v1/platform/billing/subscriptions/{tenantId}', [SubscriptionController::class, 'show'])
    ->name('billing.subscriptions.show');

Route::middleware(['tenant.context', 'auth:sanctum', 'merchant.tenant', 'permission.check:billing.read'])->group(function (): void {
    Route::get('/v1/platform/billing/subscription', [MerchantBillingController::class, 'subscription'])
        ->name('billing.subscription.show');

    Route::get('/v1/platform/billing/invoices', [MerchantBillingController::class, 'invoices'])
        ->name('billing.invoices.index');

    Route::get('/v1/platform/billing/invoices/{id}/pdf', InvoicePdfController::class)
        ->name('billing.invoices.pdf');

    Route::get('/v1/platform/billing/settings', [TenantBillingSettingsController::class, 'show'])
        ->name('billing.settings.show');
});

Route::middleware(['tenant.context', 'auth:sanctum', 'merchant.tenant', 'permission.check:billing.write'])->group(function (): void {
    Route::post('/v1/platform/billing/subscriptions/{tenantId}/initialize-payment', InitializeSubscriptionPaymentController::class)
        ->middleware('idempotency')
        ->name('billing.subscriptions.initialize-payment');

    Route::post('/v1/platform/billing/subscriptions/{tenantId}/activate', ActivateSubscriptionController::class)
        ->name('billing.subscriptions.activate');

    Route::put('/v1/platform/billing/settings', [TenantBillingSettingsController::class, 'update'])
        ->name('billing.settings.update');
});
