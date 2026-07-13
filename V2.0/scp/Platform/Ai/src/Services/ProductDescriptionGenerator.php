<?php

declare(strict_types=1);

namespace Platform\Ai\Services;

use Platform\Ai\Contracts\CompletionRequest;
use Platform\Ai\Contracts\CompletionResult;

final class ProductDescriptionGenerator
{
    public const FEATURE_KEY = 'product_description';

    public function __construct(
        private readonly ModelGateway $gateway,
        private readonly AiAccessPolicy $policy,
    ) {}

    /**
     * @param  list<string>  $keywords
     */
    public function generate(string $tenantId, string $title, array $keywords): CompletionResult
    {
        $this->policy->assertEnabled($tenantId);
        $this->policy->assertWithinDailyLimit($tenantId);

        $template = $this->policy->activeTemplate(self::FEATURE_KEY);
        $keywordList = implode(', ', array_values(array_filter(array_map('trim', $keywords))));

        $userPrompt = $this->policy->render($template->user_prompt_template, [
            'title' => $title,
            'keywords' => $keywordList !== '' ? $keywordList : 'general merchandise',
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
