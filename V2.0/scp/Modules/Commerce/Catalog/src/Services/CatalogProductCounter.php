<?php

declare(strict_types=1);

namespace Modules\Commerce\Catalog\Services;

use Modules\Commerce\Catalog\Models\Product;
use Platform\Billing\Contracts\TenantProductCounter;

final class CatalogProductCounter implements TenantProductCounter
{
    public function count(string $tenantId): int
    {
        return Product::query()
            ->where('tenant_id', $tenantId)
            ->count();
    }
}
