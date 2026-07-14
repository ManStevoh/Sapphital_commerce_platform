<?php

declare(strict_types=1);

namespace Platform\Ai\Services;

use Platform\Ai\Contracts\CompletionRequest;
use Platform\Ai\Contracts\CompletionResult;

final class CollectionDescriptionGenerator
{
    public const FEATURE_KEY = 'collection_description';

    public function __construct(
        private readonly ModelGateway $gateway,
        private readonly AiAccessPolicy $policy,
    ) {}

    /**
     * @param  array<string, mixed>|null  $rules
     */
    public function generate(
        string $tenantId,
        string $title,
        string $type,
        ?array $rules = null,
    ): CompletionResult {
        $this->policy->assertEnabled($tenantId);
        $this->policy->assertWithinDailyLimit($tenantId);

        $template = $this->policy->activeTemplate(self::FEATURE_KEY);
        $rulesSummary = $this->summarizeRules($rules);

        $userPrompt = $this->policy->render($template->user_prompt_template, [
            'title' => $title,
            'type' => $type !== '' ? $type : 'manual',
            'rules' => $rulesSummary,
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

    /**
     * @param  array<string, mixed>|null  $rules
     */
    private function summarizeRules(?array $rules): string
    {
        if ($rules === null || $rules === []) {
            return 'no automated rules (manual collection)';
        }

        $encoded = json_encode($rules, JSON_UNESCAPED_SLASHES);

        return is_string($encoded) ? mb_substr($encoded, 0, 500) : 'custom rules';
    }
}
