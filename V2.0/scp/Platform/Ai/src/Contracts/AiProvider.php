<?php

declare(strict_types=1);

namespace Platform\Ai\Contracts;

interface AiProvider
{
    public function name(): string;

    public function complete(CompletionRequest $request): CompletionResult;
}
