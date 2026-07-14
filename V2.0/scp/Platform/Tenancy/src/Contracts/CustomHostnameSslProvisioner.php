<?php

declare(strict_types=1);

namespace Platform\Tenancy\Contracts;

interface CustomHostnameSslProvisioner
{
    /**
     * @return array{ok: bool, status: string, message: string}
     */
    public function provision(string $domain, string $tenantId): array;
}
