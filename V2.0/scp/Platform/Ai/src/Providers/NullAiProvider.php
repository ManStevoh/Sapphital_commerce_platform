<?php

declare(strict_types=1);

namespace Platform\Ai\Providers;

use Platform\Ai\Contracts\AiProvider;
use Platform\Ai\Contracts\CompletionRequest;
use Platform\Ai\Contracts\CompletionResult;

final class NullAiProvider implements AiProvider
{
    public function name(): string
    {
        return 'null';
    }

    public function complete(CompletionRequest $request): CompletionResult
    {
        return new CompletionResult(
            text: '',
            provider: $this->name(),
            model: 'none',
            promptTokens: 0,
            completionTokens: 0,
            degraded: true,
        );
    }
}
