<?php

declare(strict_types=1);

namespace Platform\Ai\Services;

use Platform\Ai\Contracts\CompletionRequest;
use Platform\Ai\Contracts\CompletionResult;

final class SupportReplyGenerator
{
    public const FEATURE_KEY = 'support_reply';

    public function __construct(
        private readonly ModelGateway $gateway,
        private readonly AiAccessPolicy $policy,
        private readonly PiiScrubber $scrubber,
    ) {}

    /**
     * @param  array{
     *     order_number: string,
     *     status: string,
     *     total_kobo: int,
     *     items_summary: string,
     *     question: string
     * }  $context
     */
    public function generate(string $tenantId, array $context): CompletionResult
    {
        $this->policy->assertEnabled($tenantId);
        $this->policy->assertWithinDailyLimit($tenantId);

        $template = $this->policy->activeTemplate(self::FEATURE_KEY);
        $totalLabel = 'NGN '.number_format(max(0, (int) $context['total_kobo']) / 100, 2);

        $userPrompt = $this->policy->render($template->user_prompt_template, [
            'order_number' => $this->scrubber->scrub($context['order_number']),
            'status' => $this->scrubber->scrub($context['status']),
            'total_label' => $totalLabel,
            'items_summary' => $this->scrubber->scrub($context['items_summary']),
            'question' => $this->scrubber->scrub($context['question']),
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
