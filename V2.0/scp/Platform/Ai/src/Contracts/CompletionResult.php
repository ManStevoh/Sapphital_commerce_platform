<?php

declare(strict_types=1);

namespace Platform\Ai\Contracts;

final readonly class CompletionResult
{
    public function __construct(
        public string $text,
        public string $provider,
        public string $model,
        public int $promptTokens,
        public int $completionTokens,
        public bool $degraded = false,
    ) {}

    public function totalTokens(): int
    {
        return $this->promptTokens + $this->completionTokens;
    }
}
