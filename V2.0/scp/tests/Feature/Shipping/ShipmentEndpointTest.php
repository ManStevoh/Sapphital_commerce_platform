<?php

declare(strict_types=1);

namespace Tests\Feature\Shipping;

use Illuminate\Support\Str;
use Modules\Commerce\Orders\Models\Order;
use Modules\Commerce\Orders\Models\OrderItem;
use Modules\Commerce\Orders\Services\OrderService;
use Modules\Commerce\Shipping\Models\Shipment;
use Platform\Tenancy\Models\Tenant;
use Tests\Feature\PlatformTestCase;

final class ShipmentEndpointTest extends PlatformTestCase
{
    public function test_create_shipment_from_paid_order(): void
    {
        $tenant = $this->createTenant();
        $order = $this->createPaidOrder($tenant);
        $token = $this->createMerchantForTenant($tenant)->createToken('test')->plainTextToken;

        $response = $this->postJson('/api/v1/commerce/shipping/shipments/from-order', [
            'order_id' => $order->id,
        ], $this->merchantAuthHeaders($tenant->id, $token));

        $response->assertCreated()
            ->assertJsonPath('data.status', 'pending')
            ->assertJsonPath('data.carrier', 'manual')
            ->assertJsonPath('data.order_id', $order->id)
            ->assertJsonPath('data.tenant_id', $tenant->id)
            ->assertJsonCount(1, 'data.lines')
            ->assertJsonPath('data.lines.0.order_item_id', $order->items->first()->id)
            ->assertJsonPath('data.lines.0.quantity', 2);

        $shipmentId = $response->json('data.id');

        $this->assertNotNull($shipmentId);
        $this->assertDatabaseHas('shipments', [
            'id' => $shipmentId,
            'tenant_id' => $tenant->id,
            'order_id' => $order->id,
            'status' => 'pending',
            'carrier' => 'manual',
        ]);

        $this->assertDatabaseHas('shipment_lines', [
            'shipment_id' => $shipmentId,
            'order_item_id' => $order->items->first()->id,
            'quantity' => 2,
        ]);
    }

    public function test_mark_shipped_with_tracking(): void
    {
        $tenant = $this->createTenant();
        $order = $this->createPaidOrder($tenant);
        $token = $this->createMerchantForTenant($tenant)->createToken('test')->plainTextToken;

        $create = $this->postJson('/api/v1/commerce/shipping/shipments/from-order', [
            'order_id' => $order->id,
        ], $this->merchantAuthHeaders($tenant->id, $token));

        $create->assertCreated();
        $shipmentId = $create->json('data.id');

        $response = $this->patchJson('/api/v1/commerce/shipping/shipments/'.$shipmentId.'/ship', [
            'tracking_number' => 'TRK-123456',
            'tracking_url' => 'https://track.example.com/TRK-123456',
        ], $this->merchantAuthHeaders($tenant->id, $token));

        $response->assertOk()
            ->assertJsonPath('data.status', 'in_transit')
            ->assertJsonPath('data.tracking_number', 'TRK-123456')
            ->assertJsonPath('data.tracking_url', 'https://track.example.com/TRK-123456');

        $this->assertNotNull($response->json('data.shipped_at'));

        $this->assertDatabaseHas('shipments', [
            'id' => $shipmentId,
            'status' => 'in_transit',
            'tracking_number' => 'TRK-123456',
            'tracking_url' => 'https://track.example.com/TRK-123456',
        ]);
    }

    public function test_mark_delivered_updates_order_to_fulfilled(): void
    {
        $tenant = $this->createTenant();
        $order = $this->createPaidOrder($tenant);
        $token = $this->createMerchantForTenant($tenant)->createToken('test')->plainTextToken;

        $create = $this->postJson('/api/v1/commerce/shipping/shipments/from-order', [
            'order_id' => $order->id,
        ], $this->merchantAuthHeaders($tenant->id, $token));

        $shipmentId = $create->json('data.id');

        $this->patchJson('/api/v1/commerce/shipping/shipments/'.$shipmentId.'/ship', [
            'tracking_number' => 'TRK-789',
        ], $this->merchantAuthHeaders($tenant->id, $token))->assertOk();

        $response = $this->patchJson('/api/v1/commerce/shipping/shipments/'.$shipmentId.'/deliver', [], $this->merchantAuthHeaders($tenant->id, $token));

        $response->assertOk()
            ->assertJsonPath('data.status', 'delivered');

        $this->assertNotNull($response->json('data.delivered_at'));

        $this->assertDatabaseHas('shipments', [
            'id' => $shipmentId,
            'status' => 'delivered',
        ]);

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status' => Order::STATUS_FULFILLED,
        ]);
    }

    public function test_shipment_data_is_isolated_by_tenant(): void
    {
        $tenantA = $this->createTenant('tenant-a');
        $tenantB = $this->createTenant('tenant-b');

        $orderA = $this->createPaidOrder($tenantA);
        $orderB = $this->createPaidOrder($tenantB);

        $shipmentA = Shipment::query()->create([
            'tenant_id' => $tenantA->id,
            'order_id' => $orderA->id,
            'status' => Shipment::STATUS_PENDING,
            'carrier' => Shipment::CARRIER_MANUAL,
        ]);

        Shipment::query()->create([
            'tenant_id' => $tenantB->id,
            'order_id' => $orderB->id,
            'status' => Shipment::STATUS_PENDING,
            'carrier' => Shipment::CARRIER_MANUAL,
        ]);

        $list = $this->getJson('/api/v1/commerce/shipping/shipments', [
            'X-Tenant-ID' => $tenantA->id,
        ]);

        $list->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $shipmentA->id);

        $show = $this->getJson('/api/v1/commerce/shipping/shipments/'.$shipmentA->id, [
            'X-Tenant-ID' => $tenantB->id,
        ]);

        $show->assertNotFound()
            ->assertJson([
                'message' => 'Shipment not found.',
            ]);
    }

    public function test_mark_paid_auto_creates_pending_shipment(): void
    {
        $tenant = $this->createTenant();
        $order = $this->createPendingOrder($tenant);

        app(OrderService::class)->markPaid($order->id, 'pay_ref_'.Str::random(8));

        $this->assertDatabaseHas('shipments', [
            'tenant_id' => $tenant->id,
            'order_id' => $order->id,
            'status' => Shipment::STATUS_PENDING,
        ]);
    }

    private function createTenant(string $prefix = 'shipments'): Tenant
    {
        return Tenant::query()->create([
            'slug' => $prefix.'-'.Str::random(8),
            'name' => ucfirst($prefix).' Tenant',
            'status' => 'active',
            'country' => 'NG',
        ]);
    }

    private function createPaidOrder(Tenant $tenant): Order
    {
        $order = $this->createPendingOrder($tenant);

        $order->update([
            'status' => Order::STATUS_PAID,
        ]);

        return $order->fresh(['items']);
    }

    private function createPendingOrder(Tenant $tenant): Order
    {
        $order = Order::query()->create([
            'tenant_id' => $tenant->id,
            'checkout_session_id' => null,
            'order_number' => 'ORD-'.Str::upper(Str::random(6)),
            'status' => Order::STATUS_PENDING,
            'currency' => 'NGN',
            'subtotal_kobo' => 5_000,
            'total_kobo' => 5_000,
        ]);

        OrderItem::query()->create([
            'order_id' => $order->id,
            'product_id' => (string) Str::uuid(),
            'product_name' => 'Shippable Product',
            'quantity' => 2,
            'unit_price_kobo' => 2_500,
            'line_total_kobo' => 5_000,
        ]);

        return $order->load('items');
    }
}
