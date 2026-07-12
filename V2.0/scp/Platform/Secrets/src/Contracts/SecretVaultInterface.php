<?php

declare(strict_types=1);

namespace Platform\Secrets\Contracts;

interface SecretVaultInterface
{
    public function get(string $key): ?string;

    public function set(string $key, string $value): void;
}
