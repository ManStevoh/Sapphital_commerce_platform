<?php

declare(strict_types=1);

namespace Platform\Tenancy\Testing;

final class IsolationManifest
{
    /**
     * @return list<class-string>
     */
    public function models(): array
    {
        /** @var list<class-string> $models */
        $models = config('tenant-isolation.models', []);

        return $models;
    }

    public function sessionVariable(): string
    {
        return (string) config('tenant-isolation.session_variable', 'app.current_tenant_id');
    }

    public function shortClassName(string $class): string
    {
        $parts = explode('\\', $class);

        return (string) end($parts);
    }
}
