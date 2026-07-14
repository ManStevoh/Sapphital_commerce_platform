<?php

declare(strict_types=1);

namespace Platform\Identity\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Laravel\Sanctum\PersonalAccessToken;
use Platform\Identity\Models\MerchantUser;
use Platform\Identity\Services\MerchantMfaService;
use Symfony\Component\HttpFoundation\Response;

final class MerchantSessionController
{
    public function __construct(
        private readonly MerchantMfaService $mfa,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $user = $this->requireFullAccessMerchant($request);

        if ($user instanceof JsonResponse) {
            return $user;
        }

        $current = $user->currentAccessToken();
        $currentId = $current instanceof PersonalAccessToken ? $current->id : null;

        $tokens = $user->tokens()
            ->orderByDesc('last_used_at')
            ->orderByDesc('created_at')
            ->limit(50)
            ->get()
            ->map(static function (PersonalAccessToken $token) use ($currentId): array {
                return [
                    'id' => (string) $token->id,
                    'name' => $token->name,
                    'abilities' => $token->abilities,
                    'last_used_at' => $token->last_used_at?->toIso8601String(),
                    'created_at' => $token->created_at?->toIso8601String(),
                    'is_current' => $currentId !== null && (string) $token->id === (string) $currentId,
                ];
            })
            ->values()
            ->all();

        return response()->json([
            'data' => $tokens,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $user = $this->requireFullAccessMerchant($request);

        if ($user instanceof JsonResponse) {
            return $user;
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'min:2', 'max:64'],
        ]);

        $token = $this->mfa->issueFullAccessToken($user, $validated['name']);

        return response()->json([
            'data' => [
                'id' => (string) $token->accessToken->id,
                'name' => $token->accessToken->name,
                'token' => $token->plainTextToken,
                'token_type' => 'Bearer',
                'abilities' => $token->accessToken->abilities,
                'created_at' => $token->accessToken->created_at?->toIso8601String(),
            ],
        ], Response::HTTP_CREATED);
    }

    public function destroy(Request $request, string $id): JsonResponse
    {
        $user = $this->requireFullAccessMerchant($request);

        if ($user instanceof JsonResponse) {
            return $user;
        }

        $token = $user->tokens()->whereKey($id)->first();

        if ($token === null) {
            return response()->json([
                'message' => 'Session not found.',
            ], Response::HTTP_NOT_FOUND);
        }

        $token->delete();

        return response()->json([
            'message' => 'Session revoked.',
        ]);
    }

    private function requireFullAccessMerchant(Request $request): MerchantUser|JsonResponse
    {
        $user = $request->user();

        if (! $user instanceof MerchantUser) {
            $bearer = $request->bearerToken();

            if (is_string($bearer) && $bearer !== '') {
                $accessToken = PersonalAccessToken::findToken($bearer);
                $tokenable = $accessToken?->tokenable;

                if ($tokenable instanceof MerchantUser) {
                    $user = $tokenable;
                    $request->setUserResolver(static fn (): MerchantUser => $tokenable);

                    if ($this->isMfaPending($accessToken)) {
                        return $this->mfaPendingResponse($accessToken);
                    }

                    return $user;
                }
            }

            return response()->json([
                'message' => 'Unauthenticated.',
            ], Response::HTTP_UNAUTHORIZED);
        }

        $accessToken = $user->currentAccessToken();

        if ($accessToken instanceof PersonalAccessToken) {
            $bearer = $request->bearerToken();

            if (is_string($bearer) && $bearer !== '') {
                $fromBearer = PersonalAccessToken::findToken($bearer);

                if ($fromBearer instanceof PersonalAccessToken) {
                    $accessToken = $fromBearer;
                }
            }

            if ($this->isMfaPending($accessToken)) {
                return $this->mfaPendingResponse($accessToken);
            }
        }

        return $user;
    }

    private function mfaPendingResponse(PersonalAccessToken $accessToken): JsonResponse
    {
        $payload = [
            'message' => 'MFA verification required.',
        ];

        $abilities = $accessToken->abilities ?? [];

        if (in_array('mfa:setup', $abilities, true)) {
            $payload['mfa_enrollment_required'] = true;
        } elseif (in_array('mfa:challenge', $abilities, true)) {
            $payload['mfa_required'] = true;
        }

        return response()->json($payload, Response::HTTP_FORBIDDEN);
    }

    private function isMfaPending(PersonalAccessToken $token): bool
    {
        $abilities = $token->abilities ?? [];

        return in_array('mfa:setup', $abilities, true)
            || in_array('mfa:challenge', $abilities, true);
    }
}
