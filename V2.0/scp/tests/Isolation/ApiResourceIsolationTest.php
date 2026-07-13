<?php

declare(strict_types=1);

namespace Tests\Isolation;

use Illuminate\Support\Str;
use Modules\Commerce\Catalog\Models\Product;
use Modules\Commerce\Orders\Models\Order;
use Modules\Commerce\Shipping\Models\Shipment;
use Platform\Billing\Enums\SubscriptionStatus;
use Platform\Billing\Models\Plan;
use Platform\Billing\Models\Subscription;
use Platform\Identity\Models\MerchantUser;
use Platform\Tenancy\Models\Tenant;
use Tests\Feature\PlatformTestCase;

/**
 * API-layer isolation — Vol 13 Ch. 04 vectors 1–4 for exposed resources.
 */
final class ApiResourceIsolationTest extends PlatformTestCase
{
    public function test_tenant_alpha_cannot_list_tenant_beta_products(): void
    {
        [$alpha, $beta] = $this->twoTenants();

        Product::query()->create([
            'tenant_id' => $beta->id,
            'name' => 'Beta Only Product',
            'slug' => 'beta-only-product',
            'price_kobo' => 50_000,
            'status' => 'published',
            'inventory_qty' => 1,
        ]);

        $response = $this->getJson('/api/v1/commerce/catalog/products', [
            'X-Tenant-ID' => $alpha->id,
        ]);

        $response->assertOk();

        $ids = collect($response->json('data'))->pluck('tenant_id')->unique()->values()->all();

        $this->assertNotContains($beta->id, $ids);
    }

    public function test_tenant_alpha_cannot_show_tenant_beta_product(): void
    {
        [$alpha, $beta] = $this->twoTenants();

        $product = Product::query()->create([
            'tenant_id' => $beta->id,
            'name' => 'Hidden Product',
            'slug' => 'hidden-product',
            'price_kobo' => 50_000,
            'status' => 'published',
            'inventory_qty' => 1,
        ]);

        $this->getJson("/api/v1/commerce/catalog/products/{$product->id}", [
            'X-Tenant-ID' => $alpha->id,
        ])->assertNotFound();
    }

    public function test_merchant_alpha_cannot_update_tenant_beta_product(): void
    {
        [$alpha, $beta] = $this->twoTenants();
        $merchant = $this->createMerchantForTenant($alpha);
        $token = $merchant->createToken('iso')->plainTextToken;

        $this->createActiveSubscription($alpha->id);

        $product = Product::query()->create([
            'tenant_id' => $beta->id,
            'name' => 'Protected Product',
            'slug' => 'protected-product',
            'price_kobo' => 50_000,
            'status' => 'published',
            'inventory_qty' => 1,
        ]);

        $this->putJson(
            "/api/v1/commerce/catalog/products/{$product->id}",
            ['name' => 'Hijacked'],
            $this->merchantAuthHeaders($beta->id, $token),
        )->assertForbidden();
    }

    public function test_tenant_alpha_cannot_show_tenant_beta_order(): void
    {
        [$alpha, $beta] = $this->twoTenants();

        $order = Order::query()->create([
            'tenant_id' => $beta->id,
            'checkout_session_id' => null,
            'order_number' => 'ORD-BETA-'.Str::upper(Str::random(4)),
            'status' => Order::STATUS_PAID,
            'currency' => 'NGN',
            'subtotal_kobo' => 10_000,
            'total_kobo' => 10_000,
        ]);

        $this->getJson("/api/v1/commerce/orders/{$order->id}", [
            'X-Tenant-ID' => $alpha->id,
        ])->assertNotFound();
    }

    public function test_tenant_alpha_cannot_show_tenant_beta_shipment(): void
    {
        [$alpha, $beta] = $this->twoTenants();

        $order = Order::query()->create([
            'tenant_id' => $beta->id,
            'checkout_session_id' => null,
            'order_number' => 'ORD-SHIP-'.Str::upper(Str::random(4)),
            'status' => Order::STATUS_PAID,
            'currency' => 'NGN',
            'subtotal_kobo' => 10_000,
            'total_kobo' => 10_000,
        ]);

        $shipment = Shipment::query()->create([
            'tenant_id' => $beta->id,
            'order_id' => $order->id,
            'status' => 'pending',
            'carrier' => 'manual',
        ]);

        $this->getJson("/api/v1/commerce/shipping/shipments/{$shipment->id}", [
            'X-Tenant-ID' => $alpha->id,
        ])->assertNotFound();
    }

    public function test_tenant_alpha_cannot_add_tenant_beta_product_to_cart(): void
    {
        [$alpha, $beta] = $this->twoTenants();

        $betaProduct = Product::query()->create([
            'tenant_id' => $beta->id,
            'name' => 'Beta Cart Product',
            'slug' => 'beta-cart-product',
            'price_kobo' => 25_000,
            'status' => 'published',
            'inventory_qty' => 1,
        ]);

        $this->postJson('/api/v1/commerce/cart/items', [
            'product_id' => $betaProduct->id,
            'quantity' => 1,
        ], [
            'X-Tenant-ID' => $alpha->id,
            'X-Session-ID' => (string) Str::uuid(),
        ])->assertNotFound();
    }

    /**
     * @return array{0: Tenant, 1: Tenant}
     */
    private function twoTenants(): array
    {
        $alpha = Tenant::query()->create([
            'slug' => 'api-alpha-'.Str::random(6),
            'name' => 'API Alpha',
            'status' => 'active',
            'country' => 'NG',
        ]);

        $beta = Tenant::query()->create([
            'slug' => 'api-beta-'.Str::random(6),
            'name' => 'API Beta',
            'status' => 'active',
            'country' => 'NG',
        ]);

        return [$alpha, $beta];
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
