<?php

declare(strict_types=1);

namespace Platform\Tenancy\Support;

final class TenantContext
{
    private static ?string $override = null;

    public static function set(?string $tenantId): void
    {
        self::$override = $tenantId;
    }

    public static function clear(): void
    {
        self::$override = null;
    }

    public static function id(): ?string
    {
        if (self::$override !== null) {
            return self::$override !== '' ? self::$override : null;
        }

        if (! app()->bound('request')) {
            return null;
        }

        $request = request();
        $tenantId = $request->attributes->get('tenant_id');

        if (! is_string($tenantId) || $tenantId === '') {
            return null;
        }

        return $tenantId;
    }
}
