<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Support\Ops\StatusCatalog;
use Illuminate\Http\JsonResponse;

final class OpsStatusController
{
    public function show(StatusCatalog $catalog): JsonResponse
    {
        return response()->json($catalog->publicStatus());
    }

    public function runbooks(StatusCatalog $catalog): JsonResponse
    {
        return response()->json([
            'data' => $catalog->runbooks(),
        ]);
    }

    public function supportMacros(StatusCatalog $catalog): JsonResponse
    {
        return response()->json([
            'data' => $catalog->supportMacros(),
        ]);
    }
}
