<?php

declare(strict_types=1);

namespace Platform\Tenancy\Testing;

final class IsolationTestGenerator
{
    public function __construct(
        private readonly IsolationManifest $manifest,
        private readonly IsolationTestRenderer $renderer,
    ) {}

    public function outputDirectory(): string
    {
        return base_path('tests/Isolation/Generated');
    }

    /**
     * @return array<string, string> path => content
     */
    public function buildFiles(): array
    {
        $files = [];

        foreach ($this->manifest->models() as $modelClass) {
            $shortName = $this->manifest->shortClassName($modelClass);
            $path = $this->outputDirectory().'/'.$shortName.'IsolationTest.php';
            $files[$path] = $this->renderer->render($modelClass);
        }

        return $files;
    }

    /**
     * @return list<string> written file paths
     */
    public function writeFiles(): array
    {
        $directory = $this->outputDirectory();

        if (! is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        $written = [];

        foreach ($this->buildFiles() as $path => $content) {
            file_put_contents($path, $content);
            $written[] = $path;
        }

        return $written;
    }
}
