<?php

declare(strict_types=1);

namespace Platform\Messaging\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Platform\Messaging\Models\OutboxEvent;
use Platform\Messaging\Models\WebhookDelivery;
use Platform\Messaging\Models\WebhookEndpoint;
use Throwable;

final class WebhookDispatcher
{
    public function __construct(
        private readonly WebhookSigner $signer,
    ) {}

    /**
     * Deliver an outbox event to subscribed endpoints.
     *
     * @return array{ok: bool, error: ?string}
     */
    public function dispatch(OutboxEvent $event): array
    {
        $endpoints = WebhookEndpoint::query()
            ->where('tenant_id', $event->tenant_id)
            ->where('status', WebhookEndpoint::STATUS_ACTIVE)
            ->limit(50)
            ->get()
            ->filter(fn (WebhookEndpoint $endpoint): bool => $endpoint->listensTo($event->event_type))
            ->values();

        if ($endpoints->isEmpty()) {
            return ['ok' => true, 'error' => null];
        }

        $errors = [];

        foreach ($endpoints as $endpoint) {
            $existing = WebhookDelivery::query()
                ->where('outbox_id', $event->id)
                ->where('endpoint_id', $endpoint->id)
                ->first();

            if ($existing !== null && $existing->status === WebhookDelivery::STATUS_SUCCESS) {
                continue;
            }

            $result = $this->deliver($event, $endpoint, $existing);

            if (! $result['ok']) {
                $errors[] = $result['error'] ?? 'delivery failed';
            }
        }

        if ($errors === []) {
            return ['ok' => true, 'error' => null];
        }

        return [
            'ok' => false,
            'error' => implode('; ', array_slice($errors, 0, 5)),
        ];
    }

    /**
     * @return array{ok: bool, error: ?string}
     */
    private function deliver(
        OutboxEvent $event,
        WebhookEndpoint $endpoint,
        ?WebhookDelivery $existing,
    ): array {
        $delivery = $existing ?? WebhookDelivery::query()->create([
            'id' => (string) Str::uuid(),
            'tenant_id' => $event->tenant_id,
            'outbox_id' => $event->id,
            'endpoint_id' => $endpoint->id,
            'status' => WebhookDelivery::STATUS_PENDING,
            'attempt' => 0,
        ]);

        $body = json_encode([
            'id' => 'evt_'.$event->id,
            'object' => 'event',
            'api_version' => '2026-07-12',
            'created_at' => ($event->created_at ?? now())->toIso8601String(),
            'topic' => $event->event_type,
            'livemode' => ! app()->environment(['local', 'testing']),
            'data' => [
                'object' => $event->payload,
            ],
        ], JSON_THROW_ON_ERROR);

        $signature = $this->signer->sign($body, (string) $endpoint->secret);

        try {
            $response = Http::timeout(5)
                ->withHeaders([
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json',
                    'User-Agent' => 'SCP-Webhook/1.0',
                    'SCP-Signature' => $signature,
                    'SCP-Event-Id' => 'evt_'.$event->id,
                ])
                ->withBody($body, 'application/json')
                ->post($endpoint->url);

            $delivery->forceFill([
                'attempt' => $delivery->attempt + 1,
                'response_code' => $response->status(),
            ]);

            if ($response->successful()) {
                $delivery->forceFill([
                    'status' => WebhookDelivery::STATUS_SUCCESS,
                    'last_error' => null,
                    'delivered_at' => now(),
                ])->save();

                return ['ok' => true, 'error' => null];
            }

            $error = 'HTTP '.$response->status();
            $delivery->forceFill([
                'status' => WebhookDelivery::STATUS_FAILED,
                'last_error' => $error,
            ])->save();

            return ['ok' => false, 'error' => $error];
        } catch (Throwable $exception) {
            $error = $exception->getMessage();
            $delivery->forceFill([
                'attempt' => $delivery->attempt + 1,
                'status' => WebhookDelivery::STATUS_FAILED,
                'last_error' => $error,
            ])->save();

            return ['ok' => false, 'error' => $error];
        }
    }
}
