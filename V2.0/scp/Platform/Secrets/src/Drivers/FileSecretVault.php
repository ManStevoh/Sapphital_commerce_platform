<?php

declare(strict_types=1);

namespace Platform\Secrets\Drivers;

use InvalidArgumentException;
use Platform\Secrets\Contracts\SecretVaultInterface;

final class FileSecretVault implements SecretVaultInterface
{
    public function __construct(
        private readonly string $storagePath,
    ) {}

    public function get(string $key): ?string
    {
        $path = $this->resolvePath($key);

        if (! is_file($path)) {
            return null;
        }

        $contents = file_get_contents($path);

        if ($contents === false) {
            return null;
        }

        return $contents;
    }

    public function set(string $key, string $value): void
    {
        $path = $this->resolvePath($key);
        $directory = dirname($path);

        if (! is_dir($directory) && ! mkdir($directory, 0700, true) && ! is_dir($directory)) {
            throw new \RuntimeException("Unable to create secrets directory: {$directory}");
        }

        if (file_put_contents($path, $value, LOCK_EX) === false) {
            throw new \RuntimeException("Unable to write secret: {$key}");
        }

        chmod($path, 0600);
    }

    private function resolvePath(string $key): string
    {
        if ($key === '' || ! preg_match('/^[a-zA-Z0-9._-]+$/', $key)) {
            throw new InvalidArgumentException("Invalid secret key: {$key}");
        }

        return rtrim($this->storagePath, '/\\').DIRECTORY_SEPARATOR.$key;
    }
}
