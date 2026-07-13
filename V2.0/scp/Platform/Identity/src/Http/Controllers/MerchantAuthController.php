<?php

declare(strict_types=1);

namespace Platform\Identity\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Platform\Identity\Models\MerchantUser;
use Platform\Identity\Services\SignupHandoffService;

final class MerchantAuthController
{
    public function login(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $user = MerchantUser::query()
            ->where('email', $validated['email'])
            ->first();

        if ($user === null || ! Hash::check($validated['password'], (string) $user->password)) {
            return response()->json([
                'message' => 'Invalid credentials.',
            ], 401);
        }

        $token = $user->createToken('merchant-api')->plainTextToken;

        return response()->json([
            'token' => $token,
            'token_type' => 'Bearer',
        ]);
    }

    public function handoff(Request $request, SignupHandoffService $handoff): JsonResponse
    {
        $validated = $request->validate([
            'handoff_token' => ['required', 'string', 'max:128'],
        ]);

        try {
            $payload = $handoff->consume($validated['handoff_token']);
        } catch (ValidationException $exception) {
            return response()->json([
                'message' => collect($exception->errors())->flatten()->first()
                    ?? 'Handoff token is invalid or expired.',
                'errors' => $exception->errors(),
            ], 422);
        }

        $user = MerchantUser::query()->find($payload['merchant_user_id']);

        if ($user === null || $user->tenant_id !== $payload['tenant_id']) {
            return response()->json([
                'message' => 'Handoff token is invalid or expired.',
            ], 422);
        }

        $token = $user->createToken('merchant-handoff')->plainTextToken;

        return response()->json([
            'token' => $token,
            'token_type' => 'Bearer',
            'tenant_id' => $payload['tenant_id'],
        ]);
    }
}
