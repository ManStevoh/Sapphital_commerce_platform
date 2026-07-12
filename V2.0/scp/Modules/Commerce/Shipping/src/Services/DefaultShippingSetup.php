<?php

declare(strict_types=1);

namespace Modules\Commerce\Shipping\Services;

use Illuminate\Support\Facades\DB;
use Modules\Commerce\Shipping\Models\ShippingRate;
use Modules\Commerce\Shipping\Models\ShippingZone;

final class DefaultShippingSetup
{
    public function ensureDefaults(string $tenantId): void
    {
        $hasZones = ShippingZone::query()
            ->where('tenant_id', $tenantId)
            ->exists();

        if ($hasZones) {
            return;
        }

        DB::transaction(function () use ($tenantId): void {
            if (ShippingZone::query()->where('tenant_id', $tenantId)->exists()) {
                return;
            }

            $zone = ShippingZone::query()->create([
                'tenant_id' => $tenantId,
                'name' => 'Nigeria',
                'countries' => ['NG'],
                'is_default' => true,
            ]);

            ShippingRate::query()->create([
                'zone_id' => $zone->id,
                'name' => 'Lagos Standard',
                'type' => ShippingRate::TYPE_FLAT,
                'price_kobo' => 150_000,
                'estimated_days_min' => 2,
                'estimated_days_max' => 5,
            ]);

            ShippingRate::query()->create([
                'zone_id' => $zone->id,
                'name' => 'Nationwide',
                'type' => ShippingRate::TYPE_FLAT,
                'price_kobo' => 350_000,
                'estimated_days_min' => 5,
                'estimated_days_max' => 10,
            ]);
        });
    }
}
