<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Platform\Billing\Http\Controllers\HealthController;
use Platform\Billing\Http\Controllers\PlanController;
use Platform\Billing\Http\Controllers\SubscriptionController;

Route::get('/v1/platform/billing/health', [HealthController::class, 'show'])
    ->name('billing.health.show');

Route::get('/v1/platform/billing/plans', [PlanController::class, 'index'])
    ->name('billing.plans.index');

Route::get('/v1/platform/billing/subscriptions/{tenantId}', [SubscriptionController::class, 'show'])
    ->name('billing.subscriptions.show');
