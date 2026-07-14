<?php

declare(strict_types=1);

namespace Platform\Messaging\Services;

use Illuminate\Support\Facades\DB;
use Platform\Messaging\Models\OutboxDeadLetter;
use Platform\Messaging\Models\OutboxEvent;

final class OutboxPoller
{
    public const MAX_RETRIES = 10;

    /** @var list<int> seconds */
    private const BACKOFF_SECONDS = [
        0,
        60,
        300,
        1800,
        7200,
        28800,
        86400,
        86400,
        86400,
        86400,
    ];

    public function __construct(
        private readonly WebhookDispatcher $dispatcher,
    ) {}

    /**
     * @return array{processed: int, published: int, dead_lettered: int, failed: int}
     */
    public function poll(int $batchSize = 100): array
    {
        $stats = [
            'processed' => 0,
            'published' => 0,
            'dead_lettered' => 0,
            'failed' => 0,
        ];

        $events = OutboxEvent::query()
            ->whereNull('published_at')
            ->where(function ($query): void {
                $query->whereNull('next_attempt_at')
                    ->orWhere('next_attempt_at', '<=', now());
            })
            ->orderBy('created_at')
            ->limit($batchSize)
            ->get();

        foreach ($events as $event) {
            $stats['processed']++;
            $result = $this->dispatcher->dispatch($event);

            if ($result['ok']) {
                $event->forceFill([
                    'published_at' => now(),
                    'next_attempt_at' => null,
                ])->save();
                $stats['published']++;

                continue;
            }

            $retryCount = $event->retry_count + 1;

            if ($retryCount >= self::MAX_RETRIES) {
                $this->deadLetter($event, $result['error'] ?? 'max retries exceeded');
                $event->delete();
                $stats['dead_lettered']++;

                continue;
            }

            $delay = self::BACKOFF_SECONDS[min($retryCount, count(self::BACKOFF_SECONDS) - 1)];

            $event->forceFill([
                'retry_count' => $retryCount,
                'next_attempt_at' => now()->addSeconds($delay),
            ])->save();
            $stats['failed']++;
        }

        return $stats;
    }

    private function deadLetter(OutboxEvent $event, string $error): void
    {
        DB::transaction(function () use ($event, $error): void {
            OutboxDeadLetter::query()->create([
                'id' => $event->id,
                'tenant_id' => $event->tenant_id,
                'aggregate_type' => $event->aggregate_type,
                'aggregate_id' => $event->aggregate_id,
                'event_type' => $event->event_type,
                'payload' => $event->payload,
                'retry_count' => $event->retry_count,
                'last_error' => $error,
                'failed_at' => now(),
            ]);
        });
    }
}
