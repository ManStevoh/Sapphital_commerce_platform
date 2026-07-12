<?php

declare(strict_types=1);

namespace Tests\Feature\Launch;

use Illuminate\Support\Str;
use Modules\Commerce\Catalog\Models\Product;
use Platform\Identity\Models\PlatformAdmin;
use Tests\Feature\PlatformTestCase;

/**
 * Phase 1 Nigeria GA engineering smoke test (94-blocker gate).
 *
 * Covers: signup → provisioning → storefront → checkout → payment → fulfillment → platform admin.
 *
 * @see SCP-IMP-021-12 (Launch Readiness Ch. 12)
 */
final class NigeriaGaFlowTest extends PlatformTestCase
{
    public function test_nigeria_ga_launch_flow_end_to_end(): void
    {
        $storeName = 'Lagos Tech Shop';
        $storeSlug = 'lagos-tech-shop';
        $merchantEmail = 'nigeria-ga@example.com';
        $merchantPassword = 'secure-password-123';

        // 1. Signup with starter plan (Nigeria market default)
        $signup = $this->postJson('/api/v1/signup', [
            'email' => $merchantEmail,
            'password' => $merchantPassword,
            'store_name' => $storeName,
            'plan_slug' => 'starter',
        ]);

        $signup->assertAccepted()
            ->assertJsonStructure([
                'tenant_id',
                'provisioning_run_id',
                'status',
                'poll_url',
            ])
            ->assertJsonPath('status', 'provisioning');

        $tenantId = $signup->json('tenant_id');
        $pollUrl = $signup->json('poll_url');

        $this->assertNotEmpty($tenantId);
        $this->assertStringContainsString($tenantId, (string) $pollUrl);

        // 2. Provisioning completed + 3 sample products seeded
        $provisioning = $this->getJson($pollUrl);

        $provisioning->assertOk()
            ->assertJsonPath('status', 'completed');

        $this->assertSame(3, Product::query()->where('tenant_id', $tenantId)->count());

        // 3. Tenant lookup by slug (storefront resolution)
        $this->getJson("/api/v1/platform/tenancy/tenants/by-slug/{$storeSlug}")
            ->assertOk()
            ->assertJsonPath('id', $tenantId)
            ->assertJsonPath('slug', $storeSlug)
            ->assertJsonPath('name', $storeName);

        // 4. Theme endpoint returns default scp-dawn for Nigeria GA
        $this->getJson('/api/v1/commerce/storefront/theme', [
            'X-Tenant-ID' => $tenantId,
        ])->assertOk()
            ->assertJsonPath('data.theme_id', 'scp-dawn')
            ->assertJsonPath('data.id', 'scp-dawn')
            ->assertJsonPath('data.market', 'NG');

        // 5. Catalog + shipping rates for cart total
        $products = $this->getJson('/api/v1/commerce/catalog/products', [
            'X-Tenant-ID' => $tenantId,
        ]);

        $products->assertOk()
            ->assertJsonCount(3, 'data');

        $productId = $products->json('data.0.id');
        $unitPriceKobo = (int) $products->json('data.0.price_kobo');
        $this->assertNotEmpty($productId);
        $this->assertGreaterThan(0, $unitPriceKobo);

        $this->getJson('/api/v1/commerce/shipping/rates?order_total_kobo='.$unitPriceKobo, [
            'X-Tenant-ID' => $tenantId,
        ])->assertOk()
            ->assertJsonCount(2, 'data');

        // 6. Add to cart → checkout → Paystack initialize + verify
        $sessionId = (string) Str::uuid();

        $this->postJson('/api/v1/commerce/cart/items', [
            'product_id' => $productId,
            'quantity' => 1,
        ], [
            'X-Tenant-ID' => $tenantId,
            'X-Session-ID' => $sessionId,
        ])->assertCreated();

        $cart = $this->getJson('/api/v1/commerce/cart', [
            'X-Tenant-ID' => $tenantId,
            'X-Session-ID' => $sessionId,
        ]);

        $cart->assertOk()
            ->assertJsonPath('data.total_kobo', $unitPriceKobo);

        $cartId = $cart->json('data.id');

        $checkout = $this->postJson('/api/v1/commerce/checkout/sessions', [
            'cart_id' => $cartId,
        ], [
            'X-Tenant-ID' => $tenantId,
            'X-Session-ID' => $sessionId,
        ]);

        $checkout->assertCreated();
        $checkoutSessionId = $checkout->json('data.session_id');
        $this->assertNotEmpty($checkoutSessionId);

        $initialize = $this->postJson('/api/v1/platform/financial-services/payments/initialize', [
            'checkout_session_id' => $checkoutSessionId,
            'email' => 'buyer@lagos-tech.test',
        ], [
            'X-Tenant-ID' => $tenantId,
        ]);

        $initialize->assertOk()
            ->assertJsonStructure(['data' => ['authorization_url', 'reference']]);

        $reference = $initialize->json('data.reference');

        $verify = $this->postJson('/api/v1/platform/financial-services/payments/verify', [
            'reference' => $reference,
        ], [
            'X-Tenant-ID' => $tenantId,
        ]);

        $verify->assertOk()
            ->assertJsonPath('data.status', 'completed')
            ->assertJsonStructure(['data' => ['order_id']]);

        $orderId = $verify->json('data.order_id');
        $this->assertNotEmpty($orderId);

        // 7. Order marked paid
        $this->getJson("/api/v1/commerce/orders/{$orderId}", [
            'X-Tenant-ID' => $tenantId,
        ])->assertOk()
            ->assertJsonPath('data.status', 'paid')
            ->assertJsonPath('data.total_kobo', $unitPriceKobo)
            ->assertJsonCount(1, 'data.items');

        // 8. Create shipment from paid order (merchant auth required)
        $merchantLogin = $this->postJson('/api/v1/auth/merchant/login', [
            'email' => $merchantEmail,
            'password' => $merchantPassword,
        ]);

        $merchantLogin->assertOk();
        $merchantToken = $merchantLogin->json('token');

        $shipment = $this->postJson('/api/v1/commerce/shipping/shipments/from-order', [
            'order_id' => $orderId,
        ], $this->merchantAuthHeaders($tenantId, $merchantToken));

        $shipment->assertCreated()
            ->assertJsonPath('data.order_id', $orderId)
            ->assertJsonPath('data.status', 'pending')
            ->assertJsonPath('data.carrier', 'manual')
            ->assertJsonCount(1, 'data.lines');

        $this->assertDatabaseHas('shipments', [
            'tenant_id' => $tenantId,
            'order_id' => $orderId,
            'status' => 'pending',
        ]);

        // 9. Platform admin lists tenant (ops visibility)
        PlatformAdmin::query()->create([
            'email' => 'ops@sapphital.test',
            'password' => 'platform-ops-secret',
        ]);

        $adminLogin = $this->postJson('/api/v1/auth/platform/login', [
            'email' => 'ops@sapphital.test',
            'password' => 'platform-ops-secret',
        ]);

        $adminLogin->assertOk();
        $adminToken = $adminLogin->json('token');
        $this->assertNotEmpty($adminToken);

        $tenants = $this->getJson('/api/v1/platform/tenants', [
            'Authorization' => 'Bearer '.$adminToken,
        ]);

        $tenants->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'slug', 'name', 'status', 'country', 'created_at'],
                ],
                'meta' => ['total'],
            ])
            ->assertJsonPath('meta.total', 1);

        $listed = collect($tenants->json('data'));
        $this->assertTrue($listed->contains('id', $tenantId));
        $this->assertTrue($listed->contains('slug', $storeSlug));
    }
}
