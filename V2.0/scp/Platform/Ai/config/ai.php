<?php

declare(strict_types=1);

return [
    'primary_provider' => env('AI_PRIMARY_PROVIDER', 'fake'),
    'fallback_provider' => env('AI_FALLBACK_PROVIDER', 'null'),
    'openai' => [
        'model' => env('AI_OPENAI_MODEL', 'gpt-4o'),
        'base_url' => env('AI_OPENAI_BASE_URL', 'https://api.openai.com/v1'),
        'timeout_seconds' => (int) env('AI_OPENAI_TIMEOUT', 8),
    ],
    'daily_limits' => [
        'starter' => 50,
        'growth' => 200,
        'pro' => 500,
        'default' => 50,
    ],
];
