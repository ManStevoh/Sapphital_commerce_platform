<?php

declare(strict_types=1);

namespace Modules\Commerce\Orders\Tests\Feature\Orders;

use Illuminate\Support\Str;
use Modules\Commerce\Orders\Models\Order;
use Modules\Commerce\Orders\Models\OrderItem;
use Modules\Commerce\Shipping\Models\Shipment;
use Platform\Tenancy\Models\Tenant;
use Tests\Feature\PlatformTestCase;

final class ReturnWindowTest extends PlatformTestCase
{
    public function test_return_rejected_when_window_expired(): void
    {
        $tenant = Tenant::query()->create([
            'slug' => 'window-'.Str::random(6),
            'name' => 'Window Tenant',
            'status' => 'active',
            'country' => 'NG',
            'settings' => ['return_window_days' => 14],
        ]);

        [$order, $item] = $this->createPaidOrderWithItem($tenant, now()->subDays(20));

        $this->postJson('/api/v1/commerce/returns/guest', [
            'order_number' => $order->order_number,
            'customer_email' => 'buyer@example.com',
            'reason' => 'defective',
            'lines' => [
                ['order_item_id' => $item->id, 'quantity' => 1],
            ],
        ], [
            'X-Tenant-ID' => $tenant->id,
        ])->assertUnprocessable()
            ->assertJsonFragment([
                'message' => 'Return window of 14 days has expired.',
            ]);
    }

    public function test_return_allowed_within_window_from_delivery_date(): void
    {
        $tenant = Tenant::query()->create([
            'slug' => 'window-ok-'.Str::random(6),
            'name' => 'Window OK Tenant',
            'status' => 'active',
            'country' => 'NG',
            'settings' => ['return_window_days' => 14],
        ]);

        [$order, $item] = $this->createPaidOrderWithItem($tenant, now()->subDays(20));

        Shipment::query()->create([
            'tenant_id' => $tenant->id,
            'order_id' => $order->id,
            'status' => 'delivered',
            'carrier' => 'manual',
            'delivered_at' => now()->subDays(5),
        ]);

        $this->postJson('/api/v1/commerce/returns/guest', [
            'order_number' => $order->order_number,
            'customer_email' => 'buyer@example.com',
            'reason' => 'defective',
            'lines' => [
                ['order_item_id' => $item->id, 'quantity' => 1],
            ],
        ], [
            'X-Tenant-ID' => $tenant->id,
        ])->assertCreated();
    }

    /**
     * @return array{0: Order, 1: OrderItem}
     */
    private function createPaidOrderWithItem(Tenant $tenant, \Illuminate\Support\Carbon $createdAt): array
    {
        $product = \Modules\Commerce\Catalog\Models\Product::query()->create([
            'tenant_id' => $tenant->id,
            'name' => 'Window Product',
            'slug' => 'window-product-'.Str::random(4),
            'price_kobo' => 500_000,
            'status' => 'published',
            'inventory_qty' => 1,
        ]);

        $order = Order::query()->create([
            'tenant_id' => $tenant->id,
            'checkout_session_id' => null,
            'order_number' => 'ORD-WIN-'.Str::upper(Str::random(4)),
            'status' => Order::STATUS_PAID,
            'currency' => 'NGN',
            'subtotal_kobo' => 500_000,
            'total_kobo' => 500_000,
            'customer_email' => 'buyer@example.com',
            'paystack_reference' => 'pay_ref_'.Str::random(8),
            'created_at' => $createdAt,
            'updated_at' => $createdAt,
        ]);

        $item = OrderItem::query()->create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'product_name' => $product->name,
            'quantity' => 1,
            'unit_price_kobo' => 500_000,
            'line_total_kobo' => 500_000,
        ]);

        return [$order, $item];
    }
}
