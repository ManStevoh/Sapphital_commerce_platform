<?php

declare(strict_types=1);

namespace Modules\Commerce\Orders\Http\Controllers;

use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Modules\Commerce\Orders\Models\ReturnRequest;
use Modules\Commerce\Orders\Services\ReturnRequestService;
use Symfony\Component\HttpFoundation\Response;

final class ReturnRequestController
{
    public function __construct(
        private readonly ReturnRequestService $returnRequests,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $tenantId = $this->tenantId($request);

        if ($tenantId === null) {
            return $this->missingTenantResponse();
        }

        $returns = ReturnRequest::query()
            ->where('tenant_id', $tenantId)
            ->with(['lines.orderItem', 'order'])
            ->orderByDesc('requested_at')
            ->get();

        return response()->json([
            'data' => $returns->map(fn (ReturnRequest $item): array => $this->payload($item))->values(),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $tenantId = $this->tenantId($request);

        if ($tenantId === null) {
            return $this->missingTenantResponse();
        }

        $validated = $request->validate([
            'order_id' => ['required', 'uuid'],
            'reason' => ['required', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'lines' => ['required', 'array', 'min:1'],
            'lines.*.order_item_id' => ['required', 'uuid'],
            'lines.*.quantity' => ['required', 'integer', 'min:1'],
            'lines.*.restock' => ['nullable', 'boolean'],
        ]);

        try {
            $returnRequest = $this->returnRequests->create(
                $tenantId,
                $validated['order_id'],
                $validated['lines'],
                $validated['reason'],
                $validated['notes'] ?? null,
            );
        } catch (ValidationException $exception) {
            return $this->validationError($exception);
        } catch (ModelNotFoundException) {
            return $this->notFoundResponse('Order not found.');
        }

        return response()->json([
            'data' => $this->payload($returnRequest),
        ], Response::HTTP_CREATED);
    }

    public function storeGuest(Request $request): JsonResponse
    {
        $tenantId = $this->tenantId($request);

        if ($tenantId === null) {
            return $this->missingTenantResponse();
        }

        $validated = $request->validate([
            'order_number' => ['required', 'string', 'max:64'],
            'customer_email' => ['required', 'email', 'max:255'],
            'reason' => ['required', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'lines' => ['required', 'array', 'min:1'],
            'lines.*.order_item_id' => ['required', 'uuid'],
            'lines.*.quantity' => ['required', 'integer', 'min:1'],
            'lines.*.restock' => ['nullable', 'boolean'],
        ]);

        try {
            $returnRequest = $this->returnRequests->createGuestReturn(
                $tenantId,
                $validated['order_number'],
                $validated['customer_email'],
                $validated['lines'],
                $validated['reason'],
                $validated['notes'] ?? null,
            );
        } catch (ValidationException $exception) {
            return $this->validationError($exception);
        }

        return response()->json([
            'data' => $this->payload($returnRequest),
        ], Response::HTTP_CREATED);
    }

    public function lookupGuest(Request $request): JsonResponse
    {
        $tenantId = $this->tenantId($request);

        if ($tenantId === null) {
            return $this->missingTenantResponse();
        }

        $validated = $request->validate([
            'order_number' => ['required', 'string', 'max:64'],
            'customer_email' => ['required', 'email', 'max:255'],
        ]);

        try {
            $order = $this->returnRequests->lookupGuestOrder(
                $tenantId,
                $validated['order_number'],
                $validated['customer_email'],
            );
        } catch (ValidationException $exception) {
            return $this->validationError($exception);
        }

        return response()->json(['data' => $order]);
    }

    public function approve(Request $request, string $id): JsonResponse
    {
        $tenantId = $this->tenantId($request);

        if ($tenantId === null) {
            return $this->missingTenantResponse();
        }

        $validated = $request->validate([
            'issue_refund' => ['nullable', 'boolean'],
        ]);

        try {
            $returnRequest = $this->returnRequests->approve(
                $tenantId,
                $id,
                $validated['issue_refund'] ?? false,
            );
        } catch (ValidationException $exception) {
            return $this->validationError($exception);
        } catch (ModelNotFoundException) {
            return $this->notFoundResponse('Return request not found.');
        }

        return response()->json([
            'data' => $this->payload($returnRequest),
        ]);
    }

    public function ship(Request $request, string $id): JsonResponse
    {
        $tenantId = $this->tenantId($request);

        if ($tenantId === null) {
            return $this->missingTenantResponse();
        }

        try {
            $returnRequest = $this->returnRequests->markShipped($tenantId, $id);
        } catch (ValidationException $exception) {
            return $this->validationError($exception);
        } catch (ModelNotFoundException) {
            return $this->notFoundResponse('Return request not found.');
        }

        return response()->json([
            'data' => $this->payload($returnRequest),
        ]);
    }

    public function receive(Request $request, string $id): JsonResponse
    {
        $tenantId = $this->tenantId($request);

        if ($tenantId === null) {
            return $this->missingTenantResponse();
        }

        try {
            $returnRequest = $this->returnRequests->markReceived($tenantId, $id);
        } catch (ValidationException $exception) {
            return $this->validationError($exception);
        } catch (ModelNotFoundException) {
            return $this->notFoundResponse('Return request not found.');
        }

        return response()->json([
            'data' => $this->payload($returnRequest),
        ]);
    }

    public function reject(Request $request, string $id): JsonResponse
    {
        $tenantId = $this->tenantId($request);

        if ($tenantId === null) {
            return $this->missingTenantResponse();
        }

        $validated = $request->validate([
            'rejection_reason' => ['required', 'string', 'max:500'],
        ]);

        try {
            $returnRequest = $this->returnRequests->reject(
                $tenantId,
                $id,
                $validated['rejection_reason'],
            );
        } catch (ValidationException $exception) {
            return $this->validationError($exception);
        } catch (ModelNotFoundException) {
            return $this->notFoundResponse('Return request not found.');
        }

        return response()->json([
            'data' => $this->payload($returnRequest),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function payload(ReturnRequest $returnRequest): array
    {
        return [
            'id' => $returnRequest->id,
            'tenant_id' => $returnRequest->tenant_id,
            'order_id' => $returnRequest->order_id,
            'status' => $returnRequest->status->value,
            'reason' => $returnRequest->reason,
            'notes' => $returnRequest->notes,
            'rejection_reason' => $returnRequest->rejection_reason,
            'requested_at' => $returnRequest->requested_at?->toIso8601String(),
            'resolved_at' => $returnRequest->resolved_at?->toIso8601String(),
            'lines' => $returnRequest->lines->map(static fn ($line): array => [
                'id' => $line->id,
                'order_item_id' => $line->order_item_id,
                'quantity' => $line->quantity,
                'restock' => (bool) $line->restock,
            ])->values()->all(),
        ];
    }

    private function validationError(ValidationException $exception): JsonResponse
    {
        return response()->json([
            'message' => collect($exception->errors())->flatten()->first()
                ?? 'Validation failed.',
            'errors' => $exception->errors(),
        ], Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    private function notFoundResponse(string $message): JsonResponse
    {
        return response()->json(['message' => $message], Response::HTTP_NOT_FOUND);
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
