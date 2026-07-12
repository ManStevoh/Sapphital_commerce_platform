<?php

declare(strict_types=1);

namespace Platform\Billing\Contracts;

interface TenantProductCounter
{
    public function count(string $tenantId): int;
}
