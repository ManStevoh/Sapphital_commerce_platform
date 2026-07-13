<?php

declare(strict_types=1);

namespace Tests\Feature\Orders;

use Illuminate\Support\Str;
use Modules\Commerce\Catalog\Models\Product;
use Modules\Commerce\Catalog\Models\ProductDigitalAsset;
use Modules\Commerce\Orders\Models\Order;
use Modules\Commerce\Orders\Models\OrderItem;
use Platform\Identity\Enums\MerchantUserRole;
use Platform\Tenancy\Models\Tenant;
use Tests\Feature\PlatformTestCase;

final class DigitalProductReturnTest extends PlatformTestCase
{
    public function test_digital_return_blocked_after_download(): void
    {
        $tenant = $this->createTenant();
        $order = $this->createDigitalOrder($tenant);
        $item = $order->items->firstOrFail();

        $issue = $this->postJson('/api/v1/commerce/orders/digital-downloads', [
            'order_number' => $order->order_number,
            'customer_email' => $order->customer_email,
            'order_item_id' => $item->id,
        ], ['X-Tenant-ID' => $tenant->id])->assertOk();

        $downloadUrl = (string) $issue->json('data.download_url');
        $this->assertNotSame('', $downloadUrl);

        $this->get($downloadUrl)->assertOk();

        $merchant = $this->createMerchantForTenant(
            $tenant,
            'digital-returns@test.com',
            'password12345',
            MerchantUserRole::Owner,
        );
        $headers = $this->merchantAuthHeaders($tenant->id, $this->login($merchant->email));

        $this->postJson('/api/v1/commerce/returns', [
            'order_id' => $order->id,
            'reason' => 'defective',
            'lines' => [['order_item_id' => $item->id, 'quantity' => 1]],
        ], $headers)->assertStatus(422);
    }

    public function test_digital_return_allowed_before_download_with_immediate_refund(): void
    {
        $tenant = $this->createTenant();
        $order = $this->createDigitalOrder($tenant);
        $item = $order->items->firstOrFail();

        $merchant = $this->createMerchantForTenant(
            $tenant,
            'digital-pre@test.com',
            'password12345',
            MerchantUserRole::Owner,
        );
        $headers = $this->merchantAuthHeaders($tenant->id, $this->login($merchant->email));

        $create = $this->postJson('/api/v1/commerce/returns', [
            'order_id' => $order->id,
            'reason' => 'not_as_described',
            'lines' => [['order_item_id' => $item->id, 'quantity' => 1]],
        ], $headers);

        $create->assertCreated();
        $returnId = $create->json('data.id');

        $this->postJson("/api/v1/commerce/returns/{$returnId}/approve", [
            'issue_refund' => true,
        ], array_merge($headers, $this->idempotencyHeaders()))->assertOk()
            ->assertJsonPath('data.status', 'refunded');
    }

    private function createTenant(): Tenant
    {
        return Tenant::query()->create([
            'slug' => 'digital-'.Str::random(6),
            'name' => 'Digital Store',
            'status' => 'active',
            'country' => 'NG',
            'settings' => ['currency' => 'NGN'],
        ]);
    }

    private function createDigitalOrder(Tenant $tenant): Order
    {
        $product = Product::query()->create([
            'tenant_id' => $tenant->id,
            'name' => 'E-book',
            'slug' => 'ebook-'.Str::random(4),
            'price_kobo' => 2_000_00,
            'status' => 'published',
            'inventory_qty' => 0,
            'fulfillment_type' => 'digital',
        ]);

        $order = Order::query()->create([
            'tenant_id' => $tenant->id,
            'order_number' => 'ORD-'.Str::upper(Str::random(8)),
            'status' => Order::STATUS_PAID,
            'currency' => 'NGN',
            'subtotal_kobo' => 2_000_00,
            'total_kobo' => 2_000_00,
            'customer_email' => 'buyer@example.com',
            'paystack_reference' => 'pay_'.Str::random(8),
        ]);

        OrderItem::query()->create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'product_name' => $product->name,
            'fulfillment_type' => 'digital',
            'quantity' => 1,
            'unit_price_kobo' => 2_000_00,
            'line_total_kobo' => 2_000_00,
            'download_count' => 0,
            'download_limit' => 3,
        ]);

        ProductDigitalAsset::query()->create([
            'tenant_id' => $tenant->id,
            'product_id' => $product->id,
            'storage_key' => 'digital/'.$tenant->id.'/'.$product->id.'/ebook.txt',
            'original_filename' => 'ebook.txt',
            'mime_type' => 'text/plain',
            'byte_size' => 12,
            'download_limit' => 3,
        ]);

        \Illuminate\Support\Facades\Storage::disk('local')->put(
            'digital/'.$tenant->id.'/'.$product->id.'/ebook.txt',
            'hello digital',
        );

        return $order->fresh(['items']);
    }

    private function login(string $email): string
    {
        return (string) $this->postJson('/api/v1/auth/merchant/login', [
            'email' => $email,
            'password' => 'password12345',
        ])->json('token');
    }
}
