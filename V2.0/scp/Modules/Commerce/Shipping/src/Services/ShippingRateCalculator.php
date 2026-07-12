<?php

declare(strict_types=1);

namespace Modules\Commerce\Shipping\Services;

use Modules\Commerce\Shipping\Models\ShippingRate;
use Modules\Commerce\Shipping\Models\ShippingZone;

final class ShippingRateCalculator
{
    /** BR-SHP-004: free shipping when order exceeds ₦50,000 */
    public const FREE_SHIPPING_THRESHOLD_KOBO = 5_000_000;

    public function __construct(
        private readonly DefaultShippingSetup $defaultShippingSetup,
    ) {}

    /**
     * @return list<array<string, mixed>>
     */
    public function getApplicableRates(string $tenantId, int $orderTotalKobo): array
    {
        $this->defaultShippingSetup->ensureDefaults($tenantId);

        $zones = ShippingZone::query()
            ->where('tenant_id', $tenantId)
            ->with('rates')
            ->get();

        $rates = [];

        foreach ($zones as $zone) {
            foreach ($zone->rates as $rate) {
                if (! $this->isRateApplicable($rate, $orderTotalKobo)) {
                    continue;
                }

                $rates[] = $this->formatRate($rate, $orderTotalKobo);
            }
        }

        return $rates;
    }

    private function isRateApplicable(ShippingRate $rate, int $orderTotalKobo): bool
    {
        if ($rate->min_order_kobo !== null && $orderTotalKobo < $rate->min_order_kobo) {
            return false;
        }

        if ($rate->max_order_kobo !== null && $orderTotalKobo > $rate->max_order_kobo) {
            return false;
        }

        return true;
    }

    /**
     * @return array<string, mixed>
     */
    private function formatRate(ShippingRate $rate, int $orderTotalKobo): array
    {
        $effectivePriceKobo = $this->calculateEffectivePriceKobo($rate, $orderTotalKobo);

        return [
            'id' => $rate->id,
            'zone_id' => $rate->zone_id,
            'name' => $rate->name,
            'type' => $rate->type,
            'price_kobo' => $effectivePriceKobo,
            'base_price_kobo' => $rate->price_kobo,
            'is_free_shipping' => $effectivePriceKobo === 0 && $orderTotalKobo >= self::FREE_SHIPPING_THRESHOLD_KOBO,
            'estimated_days_min' => $rate->estimated_days_min,
            'estimated_days_max' => $rate->estimated_days_max,
        ];
    }

    private function calculateEffectivePriceKobo(ShippingRate $rate, int $orderTotalKobo): int
    {
        if ($orderTotalKobo >= self::FREE_SHIPPING_THRESHOLD_KOBO) {
            return 0;
        }

        return (int) $rate->price_kobo;
    }
}
