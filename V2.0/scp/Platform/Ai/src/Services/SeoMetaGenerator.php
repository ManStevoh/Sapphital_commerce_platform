<?php

declare(strict_types=1);

namespace Platform\Ai\Services;

use Platform\Ai\Contracts\CompletionRequest;
use Platform\Ai\Contracts\CompletionResult;

final class SeoMetaGenerator
{
    public const FEATURE_KEY = 'seo_meta';

    public function __construct(
        private readonly ModelGateway $gateway,
        private readonly AiAccessPolicy $policy,
    ) {}

    public function generate(string $tenantId, string $title, string $contentSummary): CompletionResult
    {
        $this->policy->assertEnabled($tenantId);
        $this->policy->assertWithinDailyLimit($tenantId);

        $template = $this->policy->activeTemplate(self::FEATURE_KEY);
        $userPrompt = $this->policy->render($template->user_prompt_template, [
            'title' => $title,
            'content' => $contentSummary,
        ]);

        return $this->gateway->complete(new CompletionRequest(
            systemPrompt: $template->system_prompt,
            userPrompt: $userPrompt,
            featureKey: self::FEATURE_KEY,
            tenantId: $tenantId,
            meta: [
                'template_version' => $template->version,
                'watermark' => 'ai-generated-draft',
            ],
        ));
    }
}
