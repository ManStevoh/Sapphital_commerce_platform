<?php

declare(strict_types=1);

namespace Platform\Ai\Services;

use Platform\Ai\Contracts\CompletionRequest;
use Platform\Ai\Contracts\CompletionResult;

final class ZeroResultSuggestGenerator
{
    public const FEATURE_KEY = 'zero_result_suggest';

    public function __construct(
        private readonly ModelGateway $gateway,
        private readonly AiAccessPolicy $policy,
    ) {}

    public function generate(string $tenantId, string $query, int $searchCount = 1): CompletionResult
    {
        $this->policy->assertEnabled($tenantId);
        $this->policy->assertWithinDailyLimit($tenantId);

        $normalized = mb_strtolower(trim($query));
        $template = $this->policy->activeTemplate(self::FEATURE_KEY);
        $userPrompt = $this->policy->render($template->user_prompt_template, [
            'query' => $normalized,
            'search_count' => (string) max(1, $searchCount),
        ]);

        return $this->gateway->complete(new CompletionRequest(
            systemPrompt: $template->system_prompt,
            userPrompt: $userPrompt,
            featureKey: self::FEATURE_KEY,
            tenantId: $tenantId,
            meta: [
                'template_version' => $template->version,
                'watermark' => 'ai-generated-draft',
                'query' => $normalized,
                'search_count' => max(1, $searchCount),
            ],
        ));
    }
}
