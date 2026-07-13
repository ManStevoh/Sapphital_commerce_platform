<?php

declare(strict_types=1);

namespace Modules\Commerce\Shipping\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Commerce\Shipping\Services\ShippingRateCalculator;
use Symfony\Component\HttpFoundation\Response;

final class ShippingRateController
{
    public function __construct(
        private readonly ShippingRateCalculator $shippingRateCalculator,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $tenantId = $this->tenantId($request);

        if ($tenantId === null) {
            return $this->missingTenantResponse();
        }

        $orderTotalKobo = $request->query('order_total_kobo');

        if ($orderTotalKobo === null || $orderTotalKobo === '') {
            return response()->json([
                'message' => 'order_total_kobo query parameter required.',
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        if (! is_numeric($orderTotalKobo) || (int) $orderTotalKobo < 0) {
            return response()->json([
                'message' => 'order_total_kobo must be a non-negative integer.',
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        if ($request->boolean('digital_only')) {
            return response()->json([
                'data' => [],
                'meta' => [
                    'shipping_required' => false,
                    'reason' => 'digital_only_order',
                ],
            ]);
        }

        $rates = $this->shippingRateCalculator->getApplicableRates(
            $tenantId,
            (int) $orderTotalKobo,
        );

        return response()->json([
            'data' => $rates,
            'meta' => [
                'shipping_required' => $rates !== [],
            ],
        ]);
    }

    private function tenantId(Request $request): ?string
    {
        $tenantId = $request->attributes->get('tenant_id');

        if (! is_string($tenantId) || $tenantId === '') {
            return null;
        }

        return $tenantId;
    }

    private function missingTenantResponse(): JsonResponse
    {
        return response()->json([
            'message' => 'Tenant context required.',
        ], Response::HTTP_FORBIDDEN);
    }
}
