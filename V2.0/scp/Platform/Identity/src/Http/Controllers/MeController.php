<?php

declare(strict_types=1);

namespace Platform\Identity\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Platform\Identity\Models\Customer;
use Platform\Identity\Models\MerchantUser;
use Platform\Identity\Models\PlatformAdmin;

final class MeController
{
    public function show(Request $request): JsonResponse
    {
        $user = $request->user();

        if ($user === null) {
            return response()->json([
                'message' => 'Unauthenticated.',
            ], 401);
        }

        return response()->json([
            'id' => $user->id,
            'type' => match (true) {
                $user instanceof MerchantUser => 'merchant',
                $user instanceof PlatformAdmin => 'platform',
                $user instanceof Customer => 'customer',
                default => 'unknown',
            },
            'email' => $user->email ?? null,
            'tenant_id' => $user instanceof MerchantUser || $user instanceof Customer
                ? $user->tenant_id
                : null,
        ]);
    }
}
