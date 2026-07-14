<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Platform\Tenancy\Http\Controllers\CustomDomainController;
use Platform\Tenancy\Http\Controllers\HealthController;
use Platform\Tenancy\Http\Controllers\TenantController;

Route::get('/v1/platform/tenancy/health', [HealthController::class, 'show'])
    ->name('tenancy.health.show');

Route::get('/v1/platform/tenancy/tenants/by-slug/{slug}', [TenantController::class, 'showBySlug'])
    ->name('tenancy.tenants.show-by-slug');

Route::get('/v1/platform/tenancy/tenants/by-host', [CustomDomainController::class, 'showByHost'])
    ->middleware('throttle:60,1')
    ->name('tenancy.tenants.show-by-host');

Route::get('/v1/platform/tenants', [TenantController::class, 'index'])
    ->middleware('platform.admin')
    ->name('tenancy.platform.tenants.index');

Route::patch('/v1/platform/tenants/{id}/status', [TenantController::class, 'updateStatus'])
    ->middleware('platform.admin')
    ->name('tenancy.platform.tenants.update-status');

Route::middleware(['tenant.context', 'auth:sanctum', 'merchant.tenant', 'permission.check:catalog.write'])->group(function (): void {
    Route::get('/v1/platform/tenancy/domains', [CustomDomainController::class, 'index'])
        ->name('tenancy.domains.index');

    Route::post('/v1/platform/tenancy/domains', [CustomDomainController::class, 'store'])
        ->name('tenancy.domains.store');

    Route::post('/v1/platform/tenancy/domains/{id}/verify', [CustomDomainController::class, 'verify'])
        ->name('tenancy.domains.verify');

    Route::delete('/v1/platform/tenancy/domains/{id}', [CustomDomainController::class, 'destroy'])
        ->name('tenancy.domains.destroy');
});
