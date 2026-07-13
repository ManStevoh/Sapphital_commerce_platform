<?php

declare(strict_types=1);

namespace Platform\Identity\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Laravel\Sanctum\PersonalAccessToken;
use Platform\Identity\Models\PlatformAdmin;
use Platform\Identity\Services\PlatformMfaService;
use Symfony\Component\HttpFoundation\Response;

final class PlatformMfaController
{
    public function __construct(
        private readonly PlatformMfaService $mfa,
    ) {}

    public function setup(Request $request): JsonResponse
    {
        $admin = $this->resolvePlatformAdmin($request);

        if ($admin === null) {
            return $this->unauthorizedResponse();
        }

        if (! $this->tokenAllows($request, 'mfa:setup')) {
            return $this->forbiddenResponse('MFA setup token required.');
        }

        $enrollment = $this->mfa->beginEnrollment($admin);

        return response()->json([
            'data' => $enrollment,
        ]);
    }

    public function confirm(Request $request): JsonResponse
    {
        $admin = $this->resolvePlatformAdmin($request);

        if ($admin === null) {
            return $this->unauthorizedResponse();
        }

        if (! $this->tokenAllows($request, 'mfa:setup')) {
            return $this->forbiddenResponse('MFA setup token required.');
        }

        $validated = $request->validate([
            'secret' => ['required', 'string', 'min:16', 'max:64'],
            'code' => ['required', 'string', 'size:6'],
        ]);

        $result = $this->mfa->confirmEnrollment(
            $admin,
            $validated['secret'],
            $validated['code'],
            $this->currentToken($request),
        );

        return response()->json($result);
    }

    public function verify(Request $request): JsonResponse
    {
        $admin = $this->resolvePlatformAdmin($request);

        if ($admin === null) {
            return $this->unauthorizedResponse();
        }

        if (! $this->tokenAllows($request, 'mfa:challenge')) {
            return $this->forbiddenResponse('MFA challenge token required.');
        }

        $validated = $request->validate([
            'code' => ['required', 'string', 'min:6', 'max:16'],
        ]);

        $token = $this->currentToken($request);

        if ($token === null) {
            return $this->unauthorizedResponse();
        }

        $result = $this->mfa->verifyChallenge($admin, $validated['code'], $token);

        return response()->json($result);
    }

    private function resolvePlatformAdmin(Request $request): ?PlatformAdmin
    {
        $user = $request->user();

        if ($user instanceof PlatformAdmin) {
            return $user;
        }

        $token = $request->bearerToken();

        if (! is_string($token) || $token === '') {
            return null;
        }

        $accessToken = PersonalAccessToken::findToken($token);
        $tokenable = $accessToken?->tokenable;

        return $tokenable instanceof PlatformAdmin ? $tokenable : null;
    }

    private function currentToken(Request $request): ?PersonalAccessToken
    {
        $token = $request->bearerToken();

        if (! is_string($token) || $token === '') {
            return null;
        }

        return PersonalAccessToken::findToken($token);
    }

    private function tokenAllows(Request $request, string $ability): bool
    {
        $token = $this->currentToken($request);

        return $token !== null && $token->can($ability);
    }

    private function unauthorizedResponse(): JsonResponse
    {
        return response()->json([
            'message' => 'Unauthenticated.',
        ], Response::HTTP_UNAUTHORIZED);
    }

    private function forbiddenResponse(string $message): JsonResponse
    {
        return response()->json([
            'message' => $message,
        ], Response::HTTP_FORBIDDEN);
    }
}
