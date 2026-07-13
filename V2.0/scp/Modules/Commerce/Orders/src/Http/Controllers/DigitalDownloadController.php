<?php

declare(strict_types=1);

namespace Modules\Commerce\Orders\Http\Controllers;

use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Modules\Commerce\Orders\Services\DigitalFulfillmentService;
use Symfony\Component\HttpFoundation\Response;

final class DigitalDownloadController
{
    public function __construct(
        private readonly DigitalFulfillmentService $digitalFulfillment,
    ) {}

    public function store(Request $request): JsonResponse
    {
        $tenantId = $this->tenantId($request);

        if ($tenantId === null) {
            return $this->missingTenantResponse();
        }

        $validated = $request->validate([
            'order_number' => ['required', 'string', 'max:64'],
            'customer_email' => ['required', 'email', 'max:255'],
            'order_item_id' => ['required', 'uuid'],
        ]);

        try {
            $item = $this->digitalFulfillment->recordDownload(
                $tenantId,
                $validated['order_number'],
                $validated['customer_email'],
                $validated['order_item_id'],
            );
        } catch (ModelNotFoundException) {
            return response()->json([
                'message' => 'Order or item not found.',
            ], Response::HTTP_NOT_FOUND);
        } catch (ValidationException $exception) {
            return response()->json([
                'message' => collect($exception->errors())->flatten()->first()
                    ?? 'Download could not be recorded.',
                'errors' => $exception->errors(),
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        return response()->json([
            'data' => [
                'order_item_id' => $item->id,
                'fulfillment_type' => $item->fulfillment_type,
                'downloaded_at' => $item->downloaded_at?->toIso8601String(),
            ],
        ]);
    }

    private function tenantId(Request $request): ?string
    {
        $tenantId = $request->attributes->get('tenant_id');

        return is_string($tenantId) && $tenantId !== '' ? $tenantId : null;
    }

    private function missingTenantResponse(): JsonResponse
    {
        return response()->json([
            'message' => 'Tenant context required.',
        ], Response::HTTP_FORBIDDEN);
    }
}
