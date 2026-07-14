<?php

declare(strict_types=1);

namespace Platform\Tenancy\Services;

use Platform\Tenancy\Contracts\CustomHostnameSslProvisioner;

/**
 * Stand-in for Cloudflare SSL for SaaS until live credentials are available.
 */
final class FakeCustomHostnameSslProvisioner implements CustomHostnameSslProvisioner
{
    public function provision(string $domain, string $tenantId): array
    {
        return [
            'ok' => true,
            'status' => 'active',
            'message' => 'Fake SSL provisioned for '.$domain,
        ];
    }
}
