<?php

declare(strict_types=1);

namespace Tests\Feature\Identity;

use Illuminate\Support\Str;
use Modules\Commerce\Orders\Models\Order;
use Platform\Identity\Models\Customer;
use Platform\Identity\Models\CustomerAddress;
use Platform\Tenancy\Models\Tenant;
use Tests\Feature\PlatformTestCase;

final class CustomerAccountEndpointTest extends PlatformTestCase
{
    public function test_customer_can_register_and_login(): void
    {
        $tenant = $this->createTenant();

        $register = $this->postJson('/api/v1/auth/customer/register', [
            'email' => 'shopper@example.com',
            'password' => 'password12345',
            'name' => 'Ada Shopper',
        ], ['X-Tenant-ID' => $tenant->id]);

        $register->assertCreated()
            ->assertJsonPath('data.email', 'shopper@example.com')
            ->assertJsonStructure(['token', 'data' => ['id']]);

        $login = $this->postJson('/api/v1/auth/customer/login', [
            'email' => 'shopper@example.com',
            'password' => 'password12345',
        ], ['X-Tenant-ID' => $tenant->id]);

        $login->assertOk()->assertJsonStructure(['token']);
    }

    public function test_register_requires_tenant_and_rejects_duplicate_email(): void
    {
        $tenant = $this->createTenant();

        $this->postJson('/api/v1/auth/customer/register', [
            'email' => 'dup@example.com',
            'password' => 'password12345',
        ])->assertForbidden();

        $this->postJson('/api/v1/auth/customer/register', [
            'email' => 'dup@example.com',
            'password' => 'password12345',
        ], ['X-Tenant-ID' => $tenant->id])->assertCreated();

        $this->postJson('/api/v1/auth/customer/register', [
            'email' => 'dup@example.com',
            'password' => 'password12345',
        ], ['X-Tenant-ID' => $tenant->id])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['email']);
    }

    public function test_customer_sees_own_orders_and_manages_addresses(): void
    {
        $tenant = $this->createTenant();
        $otherTenant = $this->createTenant('other');

        $register = $this->postJson('/api/v1/auth/customer/register', [
            'email' => 'buyer@example.com',
            'password' => 'password12345',
        ], ['X-Tenant-ID' => $tenant->id])->assertCreated();

        $token = (string) $register->json('token');
        $customerId = (string) $register->json('data.id');
        $headers = [
            'X-Tenant-ID' => $tenant->id,
            'Authorization' => 'Bearer '.$token,
        ];

        Order::query()->create([
            'tenant_id' => $tenant->id,
            'customer_id' => $customerId,
            'order_number' => 'ORD-'.Str::upper(Str::random(6)),
            'status' => Order::STATUS_PAID,
            'currency' => 'NGN',
            'subtotal_kobo' => 200_000,
            'total_kobo' => 200_000,
            'customer_email' => 'buyer@example.com',
        ]);

        Order::query()->create([
            'tenant_id' => $tenant->id,
            'order_number' => 'ORD-'.Str::upper(Str::random(6)),
            'status' => Order::STATUS_PAID,
            'currency' => 'NGN',
            'subtotal_kobo' => 50_000,
            'total_kobo' => 50_000,
            'customer_email' => 'stranger@example.com',
        ]);

        Order::query()->create([
            'tenant_id' => $otherTenant->id,
            'order_number' => 'ORD-'.Str::upper(Str::random(6)),
            'status' => Order::STATUS_PAID,
            'currency' => 'NGN',
            'subtotal_kobo' => 90_000,
            'total_kobo' => 90_000,
            'customer_email' => 'buyer@example.com',
        ]);

        $orders = $this->getJson('/api/v1/commerce/account/orders', $headers);
        $orders->assertOk()->assertJsonCount(1, 'data');

        $createAddress = $this->postJson('/api/v1/commerce/account/addresses', [
            'label' => 'Home',
            'line1' => '10 Admiralty Way',
            'city' => 'Lekki',
            'state' => 'Lagos',
            'is_default' => true,
        ], $headers);

        $createAddress->assertCreated()
            ->assertJsonPath('data.city', 'Lekki');

        $addressId = (string) $createAddress->json('data.id');

        $this->getJson('/api/v1/commerce/account/addresses', $headers)
            ->assertOk()
            ->assertJsonCount(1, 'data');

        $this->deleteJson("/api/v1/commerce/account/addresses/{$addressId}", [], $headers)
            ->assertNoContent();

        $this->assertDatabaseMissing('customer_addresses', ['id' => $addressId]);
        $this->assertSame(0, CustomerAddress::query()->where('customer_id', $customerId)->count());
        $this->assertInstanceOf(Customer::class, Customer::query()->find($customerId));
    }

    public function test_account_orders_require_customer_token(): void
    {
        $tenant = $this->createTenant();

        $this->getJson('/api/v1/commerce/account/orders', [
            'X-Tenant-ID' => $tenant->id,
        ])->assertUnauthorized();
    }

    private function createTenant(string $prefix = 'cust'): Tenant
    {
        return Tenant::query()->create([
            'slug' => $prefix.'-'.Str::random(8),
            'name' => ucfirst($prefix).' Tenant',
            'status' => 'active',
            'country' => 'NG',
        ]);
    }
}
