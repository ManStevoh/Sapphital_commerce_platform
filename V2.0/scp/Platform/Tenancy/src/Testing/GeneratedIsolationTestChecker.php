<?php

declare(strict_types=1);

namespace Platform\Tenancy\Testing;

final class GeneratedIsolationTestChecker
{
    public function __construct(
        private readonly IsolationTestGenerator $generator,
    ) {}

    /**
     * @return list<string> stale or missing file paths
     */
    public function staleFiles(): array
    {
        $stale = [];

        foreach ($this->generator->buildFiles() as $path => $expected) {
            if (! is_file($path)) {
                $stale[] = $path;

                continue;
            }

            $actual = (string) file_get_contents($path);

            if ($this->normalize($actual) !== $this->normalize($expected)) {
                $stale[] = $path;
            }
        }

        return $stale;
    }

    private function normalize(string $content): string
    {
        return str_replace("\r\n", "\n", trim($content));
    }
}
