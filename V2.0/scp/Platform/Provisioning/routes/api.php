<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Platform\Provisioning\Http\Controllers\HealthController;
use Platform\Provisioning\Http\Controllers\ProvisioningStatusController;
use Platform\Provisioning\Http\Controllers\SignupController;

Route::get('/v1/platform/provisioning/health', [HealthController::class, 'show'])
    ->name('provisioning.health.show');

Route::post('/v1/signup', [SignupController::class, 'store'])
    ->middleware(['turnstile.verify', 'throttle:10,1'])
    ->name('provisioning.signup.store');

Route::get('/v1/provisioning/{tenantId}/status', [ProvisioningStatusController::class, 'show'])
    ->name('provisioning.status.show');
