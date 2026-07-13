<?php

declare(strict_types=1);

namespace Platform\FinancialServices\Services;

use Platform\FinancialServices\Models\WebhookEvent;

final class WebhookEventRecorder
{
    public function hasBeenProcessed(string $provider, string $eventType, string $reference): bool
    {
        return WebhookEvent::query()
            ->where('provider', $provider)
            ->where('event_type', $eventType)
            ->where('reference', $reference)
            ->exists();
    }

    public function record(string $provider, string $eventType, string $reference, string $payloadHash): void
    {
        WebhookEvent::query()->firstOrCreate(
            [
                'provider' => $provider,
                'event_type' => $eventType,
                'reference' => $reference,
            ],
            [
                'payload_hash' => $payloadHash,
                'processed_at' => now(),
            ],
        );
    }
}
