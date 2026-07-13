<?php

declare(strict_types=1);

namespace Modules\Commerce\Orders\Services;

use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;
use Modules\Commerce\Orders\Models\Order;
use Modules\Commerce\Shipping\Models\Shipment;
use Platform\Tenancy\Models\Tenant;

final class ReturnWindowService
{
    public const DEFAULT_DAYS = 14;

    public const MIN_DAYS = 7;

    public const MAX_DAYS = 30;

    /**
     * @throws ValidationException
     */
    public function assertWithinWindow(Order $order): void
    {
        $windowDays = $this->windowDaysForTenant($order->tenant_id);
        $anchor = $this->deliveryAnchor($order);
        $deadline = $anchor->copy()->addDays($windowDays);

        if (now()->greaterThan($deadline)) {
            throw ValidationException::withMessages([
                'order' => ["Return window of {$windowDays} days has expired."],
            ]);
        }
    }

    public function windowDaysForTenant(string $tenantId): int
    {
        $tenant = Tenant::query()->find($tenantId);
        $settings = is_array($tenant?->settings) ? $tenant->settings : [];
        $configured = (int) ($settings['return_window_days'] ?? self::DEFAULT_DAYS);

        return max(self::MIN_DAYS, min(self::MAX_DAYS, $configured));
    }

    private function deliveryAnchor(Order $order): CarbonInterface
    {
        $deliveredAt = Shipment::query()
            ->where('order_id', $order->id)
            ->whereNotNull('delivered_at')
            ->max('delivered_at');

        if (is_string($deliveredAt) && $deliveredAt !== '') {
            return Carbon::parse($deliveredAt);
        }

        return $order->created_at ?? now();
    }
}
