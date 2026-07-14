<?php

declare(strict_types=1);

namespace Platform\Messaging\Services;

use Illuminate\Support\Str;
use Platform\Messaging\Models\OutboxEvent;

final class OutboxWriter
{
    /**
     * @param  array<string, mixed>  $payload
     */
    public function write(
        string $tenantId,
        string $aggregateType,
        string $aggregateId,
        string $eventType,
        array $payload,
        ?string $eventId = null,
    ): OutboxEvent {
        return OutboxEvent::query()->create([
            'id' => $eventId ?? (string) Str::uuid(),
            'tenant_id' => $tenantId,
            'aggregate_type' => $aggregateType,
            'aggregate_id' => $aggregateId,
            'event_type' => $eventType,
            'payload' => $payload,
            'retry_count' => 0,
            'next_attempt_at' => now(),
            'published_at' => null,
        ]);
    }
}
