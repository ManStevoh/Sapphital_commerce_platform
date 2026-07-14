<?php

declare(strict_types=1);

namespace Platform\Identity\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Platform\Identity\Models\MerchantUser;
use Platform\Identity\Services\MerchantLoginNotifier;
use Platform\Identity\Services\MerchantMfaService;
use Platform\Identity\Services\SignupHandoffService;

final class MerchantAuthController
{
    public function __construct(
        private readonly MerchantMfaService $mfa,
        private readonly MerchantLoginNotifier $loginNotifier,
    ) {}

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

        return $this->issueAuthResponse($request, $user);
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

        return $this->issueAuthResponse($request, $user, 'merchant-handoff');
    }

    private function issueAuthResponse(
        Request $request,
        MerchantUser $user,
        string $fullTokenName = 'merchant-api',
    ): JsonResponse {
        if ($this->mfa->isRequiredFor($user)) {
            if (! $this->mfa->isEnrolled($user)) {
                $pending = $this->mfa->issueSetupToken($user);

                return response()->json([
                    'mfa_enrollment_required' => true,
                    'token' => $pending->plainTextToken,
                    'token_type' => 'Bearer',
                    'tenant_id' => $user->tenant_id,
                ]);
            }

            $pending = $this->mfa->issueChallengeToken($user);

            return response()->json([
                'mfa_required' => true,
                'token' => $pending->plainTextToken,
                'token_type' => 'Bearer',
                'tenant_id' => $user->tenant_id,
            ]);
        }

        $token = $this->mfa->issueFullAccessToken($user, $fullTokenName);
        $this->loginNotifier->notify($user, $request);

        return response()->json([
            'token' => $token->plainTextToken,
            'token_type' => 'Bearer',
            'tenant_id' => $user->tenant_id,
        ]);
    }
}
