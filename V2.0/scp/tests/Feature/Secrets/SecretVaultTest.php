<?php

declare(strict_types=1);

namespace Tests\Feature\Secrets;

use Platform\Secrets\Contracts\SecretVaultInterface;
use Tests\TestCase;

final class SecretVaultTest extends TestCase
{
    private string $tempDirectory;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tempDirectory = sys_get_temp_dir().DIRECTORY_SEPARATOR.'scp-secrets-test-'.uniqid('', true);
        mkdir($this->tempDirectory, 0700, true);

        config([
            'secrets.driver' => 'file',
            'secrets.paths.default' => $this->tempDirectory,
        ]);
    }

    protected function tearDown(): void
    {
        $this->removeDirectory($this->tempDirectory);

        parent::tearDown();
    }

    public function test_vault_returns_null_for_missing_key(): void
    {
        $vault = $this->app->make(SecretVaultInterface::class);

        $this->assertNull($vault->get('missing.key'));
    }

    public function test_vault_stores_and_retrieves_secret(): void
    {
        $vault = $this->app->make(SecretVaultInterface::class);

        $vault->set('paystack.secret_key', 'sk_test_example');

        $this->assertSame('sk_test_example', $vault->get('paystack.secret_key'));
        $this->assertFileExists($this->tempDirectory.DIRECTORY_SEPARATOR.'paystack.secret_key');
    }

    public function test_vault_overwrites_existing_secret(): void
    {
        $vault = $this->app->make(SecretVaultInterface::class);

        $vault->set('connector.token', 'old-value');
        $vault->set('connector.token', 'new-value');

        $this->assertSame('new-value', $vault->get('connector.token'));
    }

    public function test_vault_is_registered_as_singleton(): void
    {
        $first = $this->app->make(SecretVaultInterface::class);
        $second = $this->app->make(SecretVaultInterface::class);

        $this->assertSame($first, $second);
    }

    private function removeDirectory(string $directory): void
    {
        if (! is_dir($directory)) {
            return;
        }

        $items = scandir($directory);

        if ($items === false) {
            return;
        }

        foreach ($items as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }

            $path = $directory.DIRECTORY_SEPARATOR.$item;

            if (is_dir($path)) {
                $this->removeDirectory($path);
            } else {
                unlink($path);
            }
        }

        rmdir($directory);
    }
}
