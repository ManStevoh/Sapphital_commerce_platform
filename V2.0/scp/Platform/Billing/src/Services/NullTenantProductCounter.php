<?php

declare(strict_types=1);

namespace Platform\Billing\Services;

use Platform\Billing\Contracts\TenantProductCounter;

final class NullTenantProductCounter implements TenantProductCounter
{
    public function count(string $tenantId): int
    {
        return 0;
    }
}
