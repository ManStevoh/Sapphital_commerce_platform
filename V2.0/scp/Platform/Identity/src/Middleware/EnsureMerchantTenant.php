<?php

declare(strict_types=1);

namespace Platform\Identity\Middleware;

use Closure;
use Illuminate\Http\Request;
use Laravel\Sanctum\PersonalAccessToken;
use Platform\Identity\Models\MerchantUser;
use Symfony\Component\HttpFoundation\Response;

final class EnsureMerchantTenant
{
    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user instanceof MerchantUser) {
            return response()->json([
                'message' => 'Unauthenticated.',
            ], Response::HTTP_UNAUTHORIZED);
        }

        $accessToken = $user->currentAccessToken();

        if ($accessToken instanceof PersonalAccessToken && $this->isMfaPending($accessToken)) {
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

        $tenantId = $request->attributes->get('tenant_id');

        if (! is_string($tenantId) || $tenantId === '' || $tenantId !== $user->tenant_id) {
            return response()->json([
                'message' => 'Forbidden.',
            ], Response::HTTP_FORBIDDEN);
        }

        return $next($request);
    }

    private function isMfaPending(PersonalAccessToken $token): bool
    {
        $abilities = $token->abilities ?? [];

        return in_array('mfa:setup', $abilities, true)
            || in_array('mfa:challenge', $abilities, true);
    }
}
