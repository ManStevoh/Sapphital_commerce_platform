<?php

declare(strict_types=1);

namespace Platform\Identity\Services;

use Platform\Identity\Enums\MerchantUserRole;

final class MerchantPermissionResolver
{
    public function allows(MerchantUserRole $role, string $permission): bool
    {
        /** @var array<string, list<string>> $matrix */
        $matrix = config('merchant-permissions', []);
        $grants = $matrix[$role->value] ?? [];

        if (in_array('*', $grants, true)) {
            return true;
        }

        return in_array($permission, $grants, true);
    }
}
