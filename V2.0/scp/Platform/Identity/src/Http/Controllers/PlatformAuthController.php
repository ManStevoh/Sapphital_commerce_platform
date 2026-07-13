<?php

declare(strict_types=1);

namespace Platform\Identity\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Platform\Identity\Models\PlatformAdmin;
use Platform\Identity\Services\PlatformMfaService;

final class PlatformAuthController
{
    public function __construct(
        private readonly PlatformMfaService $mfa,
    ) {}

    public function login(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $user = PlatformAdmin::query()
            ->where('email', $validated['email'])
            ->first();

        if ($user === null || ! Hash::check($validated['password'], (string) $user->password)) {
            return response()->json([
                'message' => 'Invalid credentials.',
            ], 401);
        }

        if (! $this->mfa->isEnforced()) {
            $token = $user->createToken('platform-api', ['platform:admin'])->plainTextToken;

            return response()->json([
                'token' => $token,
                'token_type' => 'Bearer',
            ]);
        }

        if (! $this->mfa->isEnrolled($user)) {
            $pending = $this->mfa->issueSetupToken($user);

            return response()->json([
                'mfa_enrollment_required' => true,
                'token' => $pending->plainTextToken,
                'token_type' => 'Bearer',
            ]);
        }

        $pending = $this->mfa->issueChallengeToken($user);

        return response()->json([
            'mfa_required' => true,
            'token' => $pending->plainTextToken,
            'token_type' => 'Bearer',
        ]);
    }
}
