<?php

declare(strict_types=1);

namespace Platform\Identity\Middleware;

use Closure;
use Illuminate\Http\Request;
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

        $tenantId = $request->attributes->get('tenant_id');

        if (! is_string($tenantId) || $tenantId === '' || $tenantId !== $user->tenant_id) {
            return response()->json([
                'message' => 'Forbidden.',
            ], Response::HTTP_FORBIDDEN);
        }

        return $next($request);
    }
}
