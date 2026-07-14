<?php

declare(strict_types=1);

namespace Tests\Feature\Messaging;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Modules\Commerce\Orders\Models\Order;
use Modules\Commerce\Orders\Services\OrderService;
use Platform\Messaging\Models\OutboxDeadLetter;
use Platform\Messaging\Models\OutboxEvent;
use Platform\Messaging\Models\WebhookDelivery;
use Platform\Messaging\Models\WebhookEndpoint;
use Platform\Messaging\Services\OutboxPoller;
use Platform\Messaging\Services\OutboxWriter;
use Platform\Messaging\Services\WebhookSigner;
use Platform\Tenancy\Models\Tenant;
use Tests\Feature\PlatformTestCase;

final class OutboxWebhookTest extends PlatformTestCase
{
    public function test_mark_paid_writes_order_paid_outbox_event(): void
    {
        $tenant = $this->createTenant();
        $order = Order::query()->create([
            'tenant_id' => $tenant->id,
            'checkout_session_id' => (string) Str::uuid(),
            'order_number' => 'ORD-OUTBOX-1',
            'status' => Order::STATUS_PENDING,
            'currency' => 'NGN',
            'subtotal_kobo' => 100_000,
            'total_kobo' => 100_000,
            'customer_email' => 'buyer@example.com',
        ]);

        app(OrderService::class)->markPaid($order->id, 'pay_outbox_1');

        $this->assertDatabaseHas('platform_outbox', [
            'tenant_id' => $tenant->id,
            'aggregate_id' => $order->id,
            'event_type' => 'order.paid',
            'published_at' => null,
        ]);
    }

    public function test_poller_delivers_signed_webhook_and_marks_published(): void
    {
        Http::fake([
            'https://hooks.example.com/*' => Http::response(['ok' => true], 200),
        ]);

        $tenant = $this->createTenant();
        $endpoint = WebhookEndpoint::query()->create([
            'tenant_id' => $tenant->id,
            'url' => 'https://hooks.example.com/scp',
            'topics' => ['order.paid'],
            'status' => WebhookEndpoint::STATUS_ACTIVE,
            'secret' => 'whsec_test_secret_123',
        ]);

        $event = app(OutboxWriter::class)->write(
            $tenant->id,
            'order',
            (string) Str::uuid(),
            'order.paid',
            ['id' => 'ord_1', 'status' => 'paid'],
        );

        $stats = app(OutboxPoller::class)->poll(50);

        $this->assertSame(1, $stats['published']);
        $this->assertNotNull($event->fresh()->published_at);

        $this->assertDatabaseHas('webhook_deliveries', [
            'outbox_id' => $event->id,
            'endpoint_id' => $endpoint->id,
            'status' => WebhookDelivery::STATUS_SUCCESS,
        ]);

        Http::assertSent(function ($request) use ($endpoint): bool {
            if ($request->url() !== $endpoint->url) {
                return false;
            }

            $signature = $request->header('SCP-Signature')[0] ?? '';
            $body = $request->body();

            return app(WebhookSigner::class)->verify($body, $signature, 'whsec_test_secret_123');
        });
    }

    public function test_successful_delivery_is_idempotent_on_repoll(): void
    {
        Http::fake([
            'https://hooks.example.com/*' => Http::response(['ok' => true], 200),
        ]);

        $tenant = $this->createTenant();
        WebhookEndpoint::query()->create([
            'tenant_id' => $tenant->id,
            'url' => 'https://hooks.example.com/scp',
            'topics' => ['order.paid'],
            'status' => WebhookEndpoint::STATUS_ACTIVE,
            'secret' => 'whsec_test_secret_123',
        ]);

        $event = app(OutboxWriter::class)->write(
            $tenant->id,
            'order',
            (string) Str::uuid(),
            'order.paid',
            ['id' => 'ord_2'],
        );

        app(OutboxPoller::class)->poll();
        // Simulate stuck unpublished row re-poll (should not re-POST after success).
        $event->forceFill(['published_at' => null, 'next_attempt_at' => now()])->save();
        Http::fake([
            'https://hooks.example.com/*' => Http::response(['ok' => true], 200),
        ]);
        app(OutboxPoller::class)->poll();

        Http::assertSentCount(0);
        $this->assertSame(1, WebhookDelivery::query()->where('outbox_id', $event->id)->count());
    }

    public function test_failed_deliveries_retry_then_dead_letter(): void
    {
        Http::fake([
            'https://hooks.example.com/*' => Http::response('nope', 500),
        ]);

        $tenant = $this->createTenant();
        WebhookEndpoint::query()->create([
            'tenant_id' => $tenant->id,
            'url' => 'https://hooks.example.com/scp',
            'topics' => ['order.paid'],
            'status' => WebhookEndpoint::STATUS_ACTIVE,
            'secret' => 'whsec_test_secret_123',
        ]);

        $event = app(OutboxWriter::class)->write(
            $tenant->id,
            'order',
            (string) Str::uuid(),
            'order.paid',
            ['id' => 'ord_3'],
        );

        $poller = app(OutboxPoller::class);

        for ($i = 0; $i < OutboxPoller::MAX_RETRIES - 1; $i++) {
            OutboxEvent::query()
                ->whereKey($event->id)
                ->update(['next_attempt_at' => now()->subSecond()]);
            $poller->poll();
        }

        $event->refresh();
        $this->assertNull($event->published_at);
        $this->assertSame(OutboxPoller::MAX_RETRIES - 1, $event->retry_count);

        OutboxEvent::query()
            ->whereKey($event->id)
            ->update(['next_attempt_at' => now()->subSecond()]);
        $poller->poll();

        $this->assertDatabaseMissing('platform_outbox', ['id' => $event->id]);
        $this->assertDatabaseHas('platform_outbox_dead', [
            'id' => $event->id,
            'event_type' => 'order.paid',
        ]);
        $this->assertSame(1, OutboxDeadLetter::query()->count());
    }

    public function test_merchant_can_register_and_list_webhook_endpoints(): void
    {
        $tenant = $this->createTenant();
        $merchant = $this->createMerchantForTenant($tenant);
        $token = $merchant->createToken('test')->plainTextToken;

        $created = $this->postJson('/api/v1/commerce/webhook-endpoints', [
            'url' => 'https://hooks.example.com/orders',
            'topics' => ['order.paid', 'order.created'],
            'description' => 'ERP sync',
        ], $this->merchantAuthHeaders($tenant->id, $token));

        $created->assertCreated()
            ->assertJsonPath('data.url', 'https://hooks.example.com/orders')
            ->assertJsonStructure(['data' => ['id', 'topics', 'secret']]);

        $this->getJson('/api/v1/commerce/webhook-endpoints', $this->merchantAuthHeaders($tenant->id, $token))
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonMissingPath('data.0.secret');

        $id = (string) $created->json('data.id');

        $this->deleteJson(
            '/api/v1/commerce/webhook-endpoints/'.$id,
            [],
            $this->merchantAuthHeaders($tenant->id, $token),
        )->assertOk();
    }

    public function test_webhook_endpoint_rejects_localhost(): void
    {
        $tenant = $this->createTenant();
        $merchant = $this->createMerchantForTenant($tenant);
        $token = $merchant->createToken('test')->plainTextToken;

        $this->postJson('/api/v1/commerce/webhook-endpoints', [
            'url' => 'http://localhost/hooks',
            'topics' => ['order.paid'],
        ], $this->merchantAuthHeaders($tenant->id, $token))
            ->assertUnprocessable();
    }

    private function createTenant(): Tenant
    {
        return Tenant::query()->create([
            'slug' => 'outbox-'.Str::lower(Str::random(6)),
            'name' => 'Outbox Tenant',
            'status' => 'active',
            'country' => 'NG',
        ]);
    }
}
