<?php

declare(strict_types=1);

namespace Platform\Tenancy\Testing;

final class IsolationTestRenderer
{
    public function __construct(
        private readonly IsolationManifest $manifest,
    ) {}

    public function render(string $modelClass): string
    {
        $shortName = $this->manifest->shortClassName($modelClass);
        $stubPath = base_path('stubs/isolation/model-isolation-test.php.stub');
        $stub = (string) file_get_contents($stubPath);

        return str_replace(
            ['{{ModelClass}}', '{{ShortName}}', '{{SessionVariable}}'],
            [$modelClass, $shortName, $this->manifest->sessionVariable()],
            $stub,
        );
    }
}
