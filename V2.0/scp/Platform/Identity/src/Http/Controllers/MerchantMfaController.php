<?php

declare(strict_types=1);

namespace Platform\Identity\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Laravel\Sanctum\PersonalAccessToken;
use Platform\Identity\Models\MerchantUser;
use Platform\Identity\Services\MerchantLoginNotifier;
use Platform\Identity\Services\MerchantMfaService;
use Symfony\Component\HttpFoundation\Response;

final class MerchantMfaController
{
    public function __construct(
        private readonly MerchantMfaService $mfa,
        private readonly MerchantLoginNotifier $loginNotifier,
    ) {}

    public function setup(Request $request): JsonResponse
    {
        $user = $this->resolveMerchantUser($request);

        if ($user === null) {
            return $this->unauthorizedResponse();
        }

        if (! $this->tokenAllows($request, 'mfa:setup')) {
            return $this->forbiddenResponse('MFA setup token required.');
        }

        $enrollment = $this->mfa->beginEnrollment($user);

        return response()->json([
            'data' => $enrollment,
        ]);
    }

    public function confirm(Request $request): JsonResponse
    {
        $user = $this->resolveMerchantUser($request);

        if ($user === null) {
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
            $user,
            $validated['secret'],
            $validated['code'],
            $this->currentToken($request),
        );

        $this->loginNotifier->notify($user, $request);

        return response()->json($result);
    }

    public function verify(Request $request): JsonResponse
    {
        $user = $this->resolveMerchantUser($request);

        if ($user === null) {
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

        $result = $this->mfa->verifyChallenge($user, $validated['code'], $token);

        $this->loginNotifier->notify($user, $request);

        return response()->json($result);
    }

    private function resolveMerchantUser(Request $request): ?MerchantUser
    {
        $user = $request->user();

        if ($user instanceof MerchantUser) {
            return $user;
        }

        $token = $request->bearerToken();

        if (! is_string($token) || $token === '') {
            return null;
        }

        $accessToken = PersonalAccessToken::findToken($token);
        $tokenable = $accessToken?->tokenable;

        return $tokenable instanceof MerchantUser ? $tokenable : null;
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
