<?php

declare(strict_types=1);

namespace Tests\Feature\Shipping;

use Illuminate\Support\Str;
use Modules\Commerce\Shipping\Models\ShippingRate;
use Modules\Commerce\Shipping\Models\ShippingZone;
use Modules\Commerce\Shipping\Services\ShippingRateCalculator;
use Platform\Tenancy\Models\Tenant;
use Tests\Feature\PlatformTestCase;

final class ShippingRatesEndpointTest extends PlatformTestCase
{
    public function test_list_rates_auto_creates_default_nigeria_zone_and_two_rates(): void
    {
        $tenant = $this->createTenant();

        $response = $this->getJson('/api/v1/commerce/shipping/rates?order_total_kobo=100000', [
            'X-Tenant-ID' => $tenant->id,
        ]);

        $response->assertOk()
            ->assertJsonCount(2, 'data');

        $names = collect($response->json('data'))->pluck('name')->sort()->values()->all();
        $this->assertSame(['Lagos Standard', 'Nationwide'], $names);

        $lagos = collect($response->json('data'))->firstWhere('name', 'Lagos Standard');
        $this->assertSame(150_000, $lagos['price_kobo']);
        $this->assertSame(2, $lagos['estimated_days_min']);
        $this->assertSame(5, $lagos['estimated_days_max']);

        $nationwide = collect($response->json('data'))->firstWhere('name', 'Nationwide');
        $this->assertSame(350_000, $nationwide['price_kobo']);
        $this->assertSame(5, $nationwide['estimated_days_min']);
        $this->assertSame(10, $nationwide['estimated_days_max']);

        $this->assertDatabaseHas('shipping_zones', [
            'tenant_id' => $tenant->id,
            'name' => 'Nigeria',
            'is_default' => 1,
        ]);

        $zone = ShippingZone::query()->where('tenant_id', $tenant->id)->firstOrFail();
        $this->assertSame(['NG'], $zone->countries);
        $this->assertDatabaseCount('shipping_rates', 2);
    }

    public function test_list_rates_applies_free_shipping_when_order_exceeds_threshold(): void
    {
        $tenant = $this->createTenant();

        $response = $this->getJson(
            '/api/v1/commerce/shipping/rates?order_total_kobo='.ShippingRateCalculator::FREE_SHIPPING_THRESHOLD_KOBO,
            ['X-Tenant-ID' => $tenant->id],
        );

        $response->assertOk()
            ->assertJsonCount(2, 'data');

        foreach ($response->json('data') as $rate) {
            $this->assertSame(0, $rate['price_kobo']);
            $this->assertTrue($rate['is_free_shipping']);
        }
    }

    public function test_list_rates_applies_free_shipping_above_threshold(): void
    {
        $tenant = $this->createTenant();

        $response = $this->getJson(
            '/api/v1/commerce/shipping/rates?order_total_kobo='.(ShippingRateCalculator::FREE_SHIPPING_THRESHOLD_KOBO + 1),
            ['X-Tenant-ID' => $tenant->id],
        );

        $response->assertOk();

        foreach ($response->json('data') as $rate) {
            $this->assertSame(0, $rate['price_kobo']);
            $this->assertTrue($rate['is_free_shipping']);
        }
    }

    public function test_list_rates_requires_tenant_context(): void
    {
        $response = $this->getJson('/api/v1/commerce/shipping/rates?order_total_kobo=100000');

        $response->assertForbidden()
            ->assertJson([
                'message' => 'Tenant context required.',
            ]);
    }

    public function test_list_rates_requires_order_total_kobo(): void
    {
        $tenant = $this->createTenant();

        $response = $this->getJson('/api/v1/commerce/shipping/rates', [
            'X-Tenant-ID' => $tenant->id,
        ]);

        $response->assertUnprocessable()
            ->assertJson([
                'message' => 'order_total_kobo query parameter required.',
            ]);
    }

    public function test_shipping_data_is_isolated_by_tenant(): void
    {
        $tenantA = $this->createTenant('tenant-a');
        $tenantB = $this->createTenant('tenant-b');

        $zoneA = ShippingZone::query()->create([
            'tenant_id' => $tenantA->id,
            'name' => 'Tenant A Zone',
            'countries' => ['NG'],
            'is_default' => true,
        ]);

        ShippingRate::query()->create([
            'zone_id' => $zoneA->id,
            'name' => 'Tenant A Only',
            'type' => ShippingRate::TYPE_FLAT,
            'price_kobo' => 99_000,
            'estimated_days_min' => 1,
            'estimated_days_max' => 2,
        ]);

        $response = $this->getJson('/api/v1/commerce/shipping/rates?order_total_kobo=100000', [
            'X-Tenant-ID' => $tenantB->id,
        ]);

        $response->assertOk()
            ->assertJsonCount(2, 'data');

        $names = collect($response->json('data'))->pluck('name')->all();
        $this->assertNotContains('Tenant A Only', $names);
        $this->assertContains('Lagos Standard', $names);
        $this->assertContains('Nationwide', $names);
    }

    private function createTenant(string $prefix = 'shipping'): Tenant
    {
        return Tenant::query()->create([
            'slug' => $prefix.'-'.Str::random(8),
            'name' => ucfirst($prefix).' Tenant',
            'status' => 'active',
            'country' => 'NG',
        ]);
    }
}
