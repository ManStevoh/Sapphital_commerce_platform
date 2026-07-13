<?php

declare(strict_types=1);

namespace Platform\Ai\Services;

use Illuminate\Support\Facades\Log;
use Platform\Ai\Contracts\AiProvider;
use Platform\Ai\Contracts\CompletionRequest;
use Platform\Ai\Contracts\CompletionResult;
use Platform\Ai\Models\AiUsageEvent;
use Platform\Ai\Providers\FakeAiProvider;
use Platform\Ai\Providers\NullAiProvider;
use Platform\Ai\Providers\OpenAiCompatibleProvider;
use Throwable;

final class ModelGateway
{
    public function __construct(
        private readonly PiiScrubber $scrubber,
    ) {}

    public function complete(CompletionRequest $request): CompletionResult
    {
        $scrubbed = new CompletionRequest(
            systemPrompt: $this->scrubber->scrub($request->systemPrompt),
            userPrompt: $this->scrubber->scrub($request->userPrompt),
            featureKey: $request->featureKey,
            tenantId: $request->tenantId,
            meta: $request->meta,
        );

        $primary = $this->resolveProvider((string) config('ai.primary_provider', 'fake'));
        $fallback = $this->resolveProvider((string) config('ai.fallback_provider', 'null'));

        try {
            $result = $primary->complete($scrubbed);
        } catch (Throwable $exception) {
            Log::warning('ai.primary_provider_failed', [
                'provider' => $primary->name(),
                'feature' => $request->featureKey,
                'tenant_id' => $request->tenantId,
                'error' => $exception->getMessage(),
            ]);

            try {
                $result = $fallback->complete($scrubbed);
            } catch (Throwable) {
                $result = (new FakeAiProvider)->complete($scrubbed);
            }

            if ($result->text === '') {
                $result = (new FakeAiProvider)->complete($scrubbed);
            }

            $result = new CompletionResult(
                text: $result->text,
                provider: $result->provider,
                model: $result->model,
                promptTokens: $result->promptTokens,
                completionTokens: $result->completionTokens,
                degraded: true,
            );
        }

        AiUsageEvent::query()->create([
            'tenant_id' => $request->tenantId,
            'feature_key' => $request->featureKey,
            'model' => $result->model,
            'provider' => $result->provider,
            'prompt_hash' => hash('sha256', $scrubbed->systemPrompt."\n".$scrubbed->userPrompt),
            'prompt_tokens' => $result->promptTokens,
            'completion_tokens' => $result->completionTokens,
            'total_tokens' => $result->totalTokens(),
            'was_watermarked' => true,
            'meta_json' => array_merge($request->meta, ['degraded' => $result->degraded]),
            'occurred_at' => now(),
        ]);

        return $result;
    }

    private function resolveProvider(string $name): AiProvider
    {
        return match ($name) {
            'openai' => new OpenAiCompatibleProvider(
                apiKey: (string) env('OPENAI_API_KEY', ''),
                model: (string) config('ai.openai.model', 'gpt-4o'),
                baseUrl: (string) config('ai.openai.base_url', 'https://api.openai.com/v1'),
                timeoutSeconds: (int) config('ai.openai.timeout_seconds', 8),
            ),
            'null' => new NullAiProvider,
            default => new FakeAiProvider,
        };
    }
}
