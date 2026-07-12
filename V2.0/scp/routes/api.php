<?php

use App\Http\Controllers\HealthController;
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
