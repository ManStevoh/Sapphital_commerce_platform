<?php

declare(strict_types=1);

namespace Platform\Ai\Providers;

use Illuminate\Support\Facades\Http;
use Platform\Ai\Contracts\AiProvider;
use Platform\Ai\Contracts\CompletionRequest;
use Platform\Ai\Contracts\CompletionResult;
use RuntimeException;

final class OpenAiCompatibleProvider implements AiProvider
{
    public function __construct(
        private readonly string $apiKey,
        private readonly string $model,
        private readonly string $baseUrl,
        private readonly int $timeoutSeconds = 8,
    ) {}

    public function name(): string
    {
        return 'openai';
    }

    public function complete(CompletionRequest $request): CompletionResult
    {
        if ($this->apiKey === '') {
            throw new RuntimeException('OpenAI API key is not configured.');
        }

        $response = Http::timeout($this->timeoutSeconds)
            ->withToken($this->apiKey)
            ->acceptJson()
            ->post(rtrim($this->baseUrl, '/').'/chat/completions', [
                'model' => $this->model,
                'messages' => [
                    ['role' => 'system', 'content' => $request->systemPrompt],
                    ['role' => 'user', 'content' => $request->userPrompt],
                ],
                'temperature' => 0.4,
            ]);

        if (! $response->successful()) {
            throw new RuntimeException('OpenAI completion failed: HTTP '.$response->status());
        }

        $text = (string) data_get($response->json(), 'choices.0.message.content', '');
        $promptTokens = (int) data_get($response->json(), 'usage.prompt_tokens', 0);
        $completionTokens = (int) data_get($response->json(), 'usage.completion_tokens', 0);

        return new CompletionResult(
            text: trim($text),
            provider: $this->name(),
            model: $this->model,
            promptTokens: $promptTokens,
            completionTokens: $completionTokens,
        );
    }
}
