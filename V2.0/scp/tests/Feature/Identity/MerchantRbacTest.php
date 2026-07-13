<?php

declare(strict_types=1);

namespace Tests\Feature\Identity;

use Illuminate\Support\Str;
use Modules\Commerce\Orders\Models\Order;
use Modules\Commerce\Orders\Models\OrderItem;
use Platform\Billing\Enums\SubscriptionStatus;
use Platform\Billing\Models\Plan;
use Platform\Billing\Models\Subscription;
use Platform\Identity\Enums\MerchantUserRole;
use Platform\Tenancy\Models\Tenant;
use Tests\Feature\PlatformTestCase;

final class MerchantRbacTest extends PlatformTestCase
{
    public function test_finance_role_cannot_create_products(): void
    {
        $tenant = $this->createTenant();
        $merchant = $this->createMerchantForTenant(
            $tenant,
            'finance@example.com',
            'password',
            MerchantUserRole::Finance,
        );
        $token = $merchant->createToken('rbac')->plainTextToken;

        $response = $this->postJson('/api/v1/commerce/catalog/products', [
            'name' => 'Blocked Product',
            'price_kobo' => 100_000,
            'status' => 'draft',
            'inventory_qty' => 1,
        ], $this->merchantAuthHeaders($tenant->id, $token));

        $response->assertForbidden()
            ->assertJson([
                'message' => 'Forbidden.',
            ]);
    }

    public function test_staff_role_cannot_create_shipments(): void
    {
        $tenant = $this->createTenant();
        $order = $this->createPaidOrder($tenant);
        $merchant = $this->createMerchantForTenant(
            $tenant,
            'staff@example.com',
            'password',
            MerchantUserRole::Staff,
        );
        $token = $merchant->createToken('rbac')->plainTextToken;

        $response = $this->postJson('/api/v1/commerce/shipping/shipments/from-order', [
            'order_id' => $order->id,
        ], $this->merchantAuthHeaders($tenant->id, $token));

        $response->assertForbidden()
            ->assertJson([
                'message' => 'Forbidden.',
            ]);
    }

    public function test_owner_role_can_create_products(): void
    {
        $tenant = $this->createTenant();
        $this->createActiveSubscription($tenant->id);
        $merchant = $this->createMerchantForTenant($tenant);
        $token = $merchant->createToken('rbac')->plainTextToken;

        $response = $this->postJson('/api/v1/commerce/catalog/products', [
            'name' => 'Allowed Product',
            'price_kobo' => 250_000,
            'status' => 'published',
            'inventory_qty' => 3,
        ], $this->merchantAuthHeaders($tenant->id, $token));

        $response->assertCreated()
            ->assertJsonPath('data.name', 'Allowed Product');

        $this->assertDatabaseHas('products', [
            'tenant_id' => $tenant->id,
            'name' => 'Allowed Product',
        ]);
    }

    private function createTenant(string $prefix = 'rbac'): Tenant
    {
        return Tenant::query()->create([
            'slug' => $prefix.'-'.Str::random(6),
            'name' => ucfirst($prefix).' Tenant',
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

    private function createPaidOrder(Tenant $tenant): Order
    {
        $order = Order::query()->create([
            'tenant_id' => $tenant->id,
            'checkout_session_id' => null,
            'order_number' => 'ORD-'.Str::upper(Str::random(6)),
            'status' => 'paid',
            'currency' => 'NGN',
            'subtotal_kobo' => 5_000,
            'total_kobo' => 5_000,
        ]);

        OrderItem::query()->create([
            'order_id' => $order->id,
            'product_id' => (string) Str::uuid(),
            'product_name' => 'RBAC Product',
            'quantity' => 1,
            'unit_price_kobo' => 5_000,
            'line_total_kobo' => 5_000,
        ]);

        return $order->fresh(['items']);
    }
}
