<?php

declare(strict_types=1);

namespace Tests\Feature\Orders;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Modules\Commerce\Catalog\Models\Product;
use Modules\Commerce\Catalog\Models\ProductDigitalAsset;
use Modules\Commerce\Orders\Models\Order;
use Modules\Commerce\Orders\Models\OrderItem;
use Platform\Billing\Enums\SubscriptionStatus;
use Platform\Billing\Models\Plan;
use Platform\Billing\Models\Subscription;
use Platform\Tenancy\Models\Tenant;
use Tests\Feature\PlatformTestCase;

final class DigitalDownloadSignedUrlTest extends PlatformTestCase
{
    public function test_merchant_can_upload_asset_and_buyer_gets_signed_url_with_limit(): void
    {
        Storage::fake('local');

        $tenant = $this->createTenant();
        $merchant = $this->createMerchantForTenant($tenant);
        $this->createActiveSubscription($tenant->id);
        $token = $merchant->createToken('digital')->plainTextToken;
        $headers = $this->merchantAuthHeaders($tenant->id, $token);

        $product = Product::query()->create([
            'tenant_id' => $tenant->id,
            'name' => 'PDF Pack',
            'slug' => 'pdf-'.Str::random(4),
            'price_kobo' => 5_000_00,
            'status' => 'published',
            'inventory_qty' => 0,
            'fulfillment_type' => 'digital',
        ]);

        $upload = $this->post(
            "/api/v1/commerce/catalog/products/{$product->id}/digital-asset",
            [
                'download_limit' => 2,
                'file' => UploadedFile::fake()->create('guide.pdf', 120, 'application/pdf'),
            ],
            $headers,
        );

        $upload->assertCreated()
            ->assertJsonPath('data.download_limit', 2)
            ->assertJsonPath('data.original_filename', 'guide.pdf');

        $this->assertTrue(
            ProductDigitalAsset::query()->where('product_id', $product->id)->exists(),
        );

        $order = Order::query()->create([
            'tenant_id' => $tenant->id,
            'order_number' => 'ORD-'.Str::upper(Str::random(8)),
            'status' => Order::STATUS_PAID,
            'currency' => 'NGN',
            'subtotal_kobo' => 5_000_00,
            'total_kobo' => 5_000_00,
            'customer_email' => 'buyer@example.com',
        ]);

        $item = OrderItem::query()->create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'product_name' => $product->name,
            'fulfillment_type' => 'digital',
            'quantity' => 1,
            'unit_price_kobo' => 5_000_00,
            'line_total_kobo' => 5_000_00,
            'download_count' => 0,
            'download_limit' => 2,
        ]);

        $issue = $this->postJson('/api/v1/commerce/orders/digital-downloads', [
            'order_number' => $order->order_number,
            'customer_email' => 'buyer@example.com',
            'order_item_id' => $item->id,
        ], ['X-Tenant-ID' => $tenant->id]);

        $issue->assertOk()
            ->assertJsonPath('data.downloads_remaining', 2)
            ->assertJsonStructure(['data' => ['download_url', 'expires_at']]);

        $url = (string) $issue->json('data.download_url');
        $this->get($url)->assertOk();
        $this->assertSame(1, (int) $item->fresh()->download_count);

        $this->get($url)->assertOk();
        $this->assertSame(2, (int) $item->fresh()->download_count);

        $this->get($url)->assertStatus(422);

        $this->getJson(
            '/api/v1/commerce/shipping/rates?order_total_kobo=500000&digital_only=1',
            ['X-Tenant-ID' => $tenant->id],
        )->assertOk()
            ->assertJsonCount(0, 'data')
            ->assertJsonPath('meta.shipping_required', false);
    }

    private function createTenant(): Tenant
    {
        return Tenant::query()->create([
            'slug' => 'digdl-'.Str::random(6),
            'name' => 'Digital Downloads',
            'status' => 'active',
            'country' => 'NG',
        ]);
    }

    private function createActiveSubscription(string $tenantId): void
    {
        $plan = Plan::query()->where('slug', 'starter')->firstOrFail();

        Subscription::query()->create([
            'tenant_id' => $tenantId,
            'plan_id' => $plan->id,
            'status' => SubscriptionStatus::Active,
        ]);
    }
}
