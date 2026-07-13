<?php

declare(strict_types=1);

namespace Platform\Identity\Middleware;

use Closure;
use Illuminate\Http\Request;
use Laravel\Sanctum\PersonalAccessToken;
use Platform\Identity\Models\PlatformAdmin;
use Symfony\Component\HttpFoundation\Response;

final class EnsurePlatformAdmin
{
    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        [$admin, $accessToken] = $this->resolvePlatformAdmin($request);

        if ($admin === null) {
            return response()->json([
                'message' => 'Forbidden.',
            ], Response::HTTP_FORBIDDEN);
        }

        if ($accessToken !== null && ! $accessToken->can('platform:admin')) {
            $payload = [
                'message' => 'MFA verification required.',
            ];

            if ($accessToken->can('mfa:setup')) {
                $payload['mfa_enrollment_required'] = true;
            } elseif ($accessToken->can('mfa:challenge')) {
                $payload['mfa_required'] = true;
            }

            return response()->json($payload, Response::HTTP_FORBIDDEN);
        }

        $request->setUserResolver(static fn (): PlatformAdmin => $admin);

        return $next($request);
    }

    /**
     * @return array{0: PlatformAdmin|null, 1: PersonalAccessToken|null}
     */
    private function resolvePlatformAdmin(Request $request): array
    {
        $user = $request->user();

        if ($user instanceof PlatformAdmin) {
            $token = $user->currentAccessToken();

            return [$user, $token instanceof PersonalAccessToken ? $token : null];
        }

        $token = $request->bearerToken();

        if (! is_string($token) || $token === '') {
            return [null, null];
        }

        $accessToken = PersonalAccessToken::findToken($token);
        $tokenable = $accessToken?->tokenable;

        if (! $tokenable instanceof PlatformAdmin) {
            return [null, null];
        }

        return [$tokenable, $accessToken];
    }
}
