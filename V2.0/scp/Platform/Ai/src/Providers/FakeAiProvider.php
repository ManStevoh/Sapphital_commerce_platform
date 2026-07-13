<?php

declare(strict_types=1);

namespace Platform\Ai\Providers;

use Platform\Ai\Contracts\AiProvider;
use Platform\Ai\Contracts\CompletionRequest;
use Platform\Ai\Contracts\CompletionResult;

/**
 * Deterministic in-process provider for tests and local degraded mode.
 */
final class FakeAiProvider implements AiProvider
{
    public function name(): string
    {
        return 'fake';
    }

    public function complete(CompletionRequest $request): CompletionResult
    {
        $excerpt = mb_substr(trim($request->userPrompt), 0, 180);

        return new CompletionResult(
            text: "[AI draft] {$excerpt}",
            provider: $this->name(),
            model: 'fake-local',
            promptTokens: max(1, (int) ceil(mb_strlen($request->systemPrompt.$request->userPrompt) / 4)),
            completionTokens: max(1, (int) ceil(mb_strlen($excerpt) / 4)),
            degraded: false,
        );
    }
}
