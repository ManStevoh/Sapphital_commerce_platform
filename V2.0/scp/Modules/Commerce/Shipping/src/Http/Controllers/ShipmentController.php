<?php

declare(strict_types=1);

namespace Modules\Commerce\Shipping\Http\Controllers;

use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Modules\Commerce\Orders\Models\Order;
use Modules\Commerce\Shipping\Models\Shipment;
use Modules\Commerce\Shipping\Services\ShipmentService;
use Symfony\Component\HttpFoundation\Response;

final class ShipmentController
{
    public function __construct(
        private readonly ShipmentService $shipmentService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $tenantId = $this->tenantId($request);

        if ($tenantId === null) {
            return $this->missingTenantResponse();
        }

        $shipments = Shipment::query()
            ->where('tenant_id', $tenantId)
            ->with('lines')
            ->orderByDesc('created_at')
            ->get();

        return response()->json([
            'data' => $shipments->map(fn (Shipment $shipment): array => $this->shipmentPayload($shipment))->values(),
        ]);
    }

    public function show(Request $request, string $id): JsonResponse
    {
        $tenantId = $this->tenantId($request);

        if ($tenantId === null) {
            return $this->missingTenantResponse();
        }

        $shipment = $this->findTenantShipment($tenantId, $id);

        if ($shipment === null) {
            return $this->notFoundResponse();
        }

        return response()->json([
            'data' => $this->shipmentPayload($shipment),
        ]);
    }

    public function createFromOrder(Request $request): JsonResponse
    {
        $tenantId = $this->tenantId($request);

        if ($tenantId === null) {
            return $this->missingTenantResponse();
        }

        $validated = $request->validate([
            'order_id' => ['required', 'uuid'],
        ]);

        $order = Order::query()
            ->where('tenant_id', $tenantId)
            ->where('id', $validated['order_id'])
            ->with('items')
            ->first();

        if ($order === null) {
            return $this->notFoundResponse('Order not found.');
        }

        try {
            $shipment = $this->shipmentService->createFromOrder($order);
        } catch (ValidationException $exception) {
            return response()->json([
                'message' => $exception->getMessage(),
                'errors' => $exception->errors(),
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        return response()->json([
            'data' => $this->shipmentPayload($shipment),
        ], Response::HTTP_CREATED);
    }

    public function ship(Request $request, string $id): JsonResponse
    {
        $tenantId = $this->tenantId($request);

        if ($tenantId === null) {
            return $this->missingTenantResponse();
        }

        $shipment = $this->findTenantShipment($tenantId, $id);

        if ($shipment === null) {
            return $this->notFoundResponse();
        }

        $validated = $request->validate([
            'tracking_number' => ['required', 'string', 'max:255'],
            'tracking_url' => ['nullable', 'string', 'max:2048'],
        ]);

        try {
            $shipment = $this->shipmentService->markShipped(
                $shipment->id,
                $validated['tracking_number'],
                $validated['tracking_url'] ?? null,
            );
        } catch (ValidationException $exception) {
            return response()->json([
                'message' => $exception->getMessage(),
                'errors' => $exception->errors(),
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        } catch (ModelNotFoundException) {
            return $this->notFoundResponse();
        }

        return response()->json([
            'data' => $this->shipmentPayload($shipment),
        ]);
    }

    public function deliver(Request $request, string $id): JsonResponse
    {
        $tenantId = $this->tenantId($request);

        if ($tenantId === null) {
            return $this->missingTenantResponse();
        }

        $shipment = $this->findTenantShipment($tenantId, $id);

        if ($shipment === null) {
            return $this->notFoundResponse();
        }

        try {
            $shipment = $this->shipmentService->markDelivered($shipment->id);
        } catch (ModelNotFoundException) {
            return $this->notFoundResponse();
        }

        return response()->json([
            'data' => $this->shipmentPayload($shipment),
        ]);
    }

    private function findTenantShipment(string $tenantId, string $id): ?Shipment
    {
        return Shipment::query()
            ->where('tenant_id', $tenantId)
            ->where('id', $id)
            ->with('lines')
            ->first();
    }

    /**
     * @return array<string, mixed>
     */
    private function shipmentPayload(Shipment $shipment): array
    {
        return [
            'id' => $shipment->id,
            'tenant_id' => $shipment->tenant_id,
            'order_id' => $shipment->order_id,
            'status' => $shipment->status,
            'carrier' => $shipment->carrier,
            'tracking_number' => $shipment->tracking_number,
            'tracking_url' => $shipment->tracking_url,
            'weight_grams' => $shipment->weight_grams,
            'shipped_at' => $shipment->shipped_at?->toIso8601String(),
            'delivered_at' => $shipment->delivered_at?->toIso8601String(),
            'lines' => $shipment->lines->map(static fn ($line): array => [
                'id' => $line->id,
                'order_item_id' => $line->order_item_id,
                'quantity' => $line->quantity,
            ])->values()->all(),
            'created_at' => $shipment->created_at?->toIso8601String(),
            'updated_at' => $shipment->updated_at?->toIso8601String(),
        ];
    }

    private function notFoundResponse(string $message = 'Shipment not found.'): JsonResponse
    {
        return response()->json([
            'message' => $message,
        ], Response::HTTP_NOT_FOUND);
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
