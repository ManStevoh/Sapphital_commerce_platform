<?php

declare(strict_types=1);

namespace Platform\Ai\Contracts;

final readonly class CompletionRequest
{
    /**
     * @param  array<string, mixed>  $meta
     */
    public function __construct(
        public string $systemPrompt,
        public string $userPrompt,
        public string $featureKey,
        public string $tenantId,
        public array $meta = [],
    ) {}
}
