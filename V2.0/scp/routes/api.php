<?php

use App\Http\Controllers\HealthController;
use App\Http\Controllers\OpsStatusController;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;

Route::get('/health', HealthController::class);

Route::get('/ready', function () {
    try {
        DB::connection()->getPdo();

        return response()->json([
            'status' => 'ready',
            'service' => 'scp-api',
        ]);
    } catch (Throwable $exception) {
        return response()->json([
            'status' => 'not_ready',
            'service' => 'scp-api',
        ], 503);
    }
});

Route::get('/v1/status', [OpsStatusController::class, 'show'])
    ->name('ops.status.show');

Route::get('/v1/ops/runbooks', [OpsStatusController::class, 'runbooks'])
    ->name('ops.runbooks.index');

Route::get('/v1/support/macros', [OpsStatusController::class, 'supportMacros'])
    ->name('ops.support-macros.index');
