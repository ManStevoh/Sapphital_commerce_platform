<?php

declare(strict_types=1);

namespace Platform\Secrets\Http\Controllers;

use Illuminate\Http\JsonResponse;

final class HealthController
{
    public function show(): JsonResponse
    {
        return response()->json([
            'status' => 'ok',
            'package' => 'secrets',
        ]);
    }
}
