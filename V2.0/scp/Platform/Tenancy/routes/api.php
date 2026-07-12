<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Platform\Tenancy\Http\Controllers\HealthController;
use Platform\Tenancy\Http\Controllers\TenantController;

Route::get('/v1/platform/tenancy/health', [HealthController::class, 'show'])
    ->name('tenancy.health.show');

Route::get('/v1/platform/tenancy/tenants/by-slug/{slug}', [TenantController::class, 'showBySlug'])
    ->name('tenancy.tenants.show-by-slug');

Route::get('/v1/platform/tenants', [TenantController::class, 'index'])
    ->middleware('platform.admin')
    ->name('tenancy.platform.tenants.index');

Route::patch('/v1/platform/tenants/{id}/status', [TenantController::class, 'updateStatus'])
    ->middleware('platform.admin')
    ->name('tenancy.platform.tenants.update-status');
