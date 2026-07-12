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
        $admin = $this->resolvePlatformAdmin($request);

        if ($admin === null) {
            return response()->json([
                'message' => 'Forbidden.',
            ], Response::HTTP_FORBIDDEN);
        }

        $request->setUserResolver(static fn (): PlatformAdmin => $admin);

        return $next($request);
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
}
