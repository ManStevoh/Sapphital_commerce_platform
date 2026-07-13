<?php

declare(strict_types=1);

namespace Platform\Identity\Middleware;

use Closure;
use Illuminate\Http\Request;
use Platform\Identity\Models\MerchantUser;
use Platform\Identity\Services\MerchantPermissionResolver;
use Symfony\Component\HttpFoundation\Response;

final class CheckPermission
{
    public function __construct(
        private readonly MerchantPermissionResolver $permissions,
    ) {}

    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next, string ...$permissions): Response
    {
        $user = $request->user();

        if (! $user instanceof MerchantUser) {
            return response()->json([
                'message' => 'Unauthenticated.',
            ], Response::HTTP_UNAUTHORIZED);
        }

        foreach ($permissions as $permission) {
            if (! $this->permissions->allows($user->role, $permission)) {
                return response()->json([
                    'message' => 'Forbidden.',
                ], Response::HTTP_FORBIDDEN);
            }
        }

        return $next($request);
    }
}
