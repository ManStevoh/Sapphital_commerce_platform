<?php

declare(strict_types=1);

namespace Platform\Tenancy\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Platform\Tenancy\Models\Tenant;
use Symfony\Component\HttpFoundation\Response;

final class SetTenantContext
{
    /**
     * Reserved subdomain labels that are not tenant slugs.
     *
     * @var list<string>
     */
    private const RESERVED_SUBDOMAINS = ['www', 'api', 'admin', 'platform'];

    public function handle(Request $request, Closure $next): Response
    {
        $tenantId = $this->resolveTenantId($request);

        if ($tenantId !== null) {
            if (Schema::getConnection()->getDriverName() === 'pgsql') {
                DB::statement('SET app.current_tenant_id = ?', [$tenantId]);
            }

            $request->attributes->set('tenant_id', $tenantId);
        }

        return $next($request);
    }

    private function resolveTenantId(Request $request): ?string
    {
        $headerTenantId = $request->header('X-Tenant-ID');

        if (is_string($headerTenantId) && $headerTenantId !== '') {
            return $headerTenantId;
        }

        return $this->resolveTenantIdFromSubdomain($request);
    }

    private function resolveTenantIdFromSubdomain(Request $request): ?string
    {
        $hostParts = explode('.', $this->resolveHost($request));

        if (count($hostParts) < 3) {
            return null;
        }

        $slug = strtolower($hostParts[0]);

        if (in_array($slug, self::RESERVED_SUBDOMAINS, true)) {
            return null;
        }

        $tenant = Tenant::query()->where('slug', $slug)->first();

        return $tenant?->id;
    }

    private function resolveHost(Request $request): string
    {
        $host = $request->getHost();

        if ($host !== '' && ! in_array($host, ['localhost', '127.0.0.1'], true)) {
            return $host;
        }

        $headerHost = $request->header('Host');

        if (is_string($headerHost) && $headerHost !== '') {
            return explode(':', $headerHost)[0];
        }

        return $host;
    }
}
