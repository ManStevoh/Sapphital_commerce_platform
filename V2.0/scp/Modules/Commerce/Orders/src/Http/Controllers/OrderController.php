<?php

declare(strict_types=1);

namespace Modules\Commerce\Orders\Http\Controllers;

use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Modules\Commerce\Checkout\Models\CheckoutSession;
use Modules\Commerce\Orders\Models\Order;
use Modules\Commerce\Orders\Services\OrderService;
use Symfony\Component\HttpFoundation\Response;

final class OrderController
{
    public function __construct(
        private readonly OrderService $orderService,
        private readonly \Platform\FinancialServices\Services\RefundService $refundService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $tenantId = $this->tenantId($request);

        if ($tenantId === null) {
            return $this->missingTenantResponse();
        }

        $orders = Order::query()
            ->where('tenant_id', $tenantId)
            ->with('items')
            ->orderByDesc('created_at')
            ->get();

        return response()->json([
            'data' => $orders->map(fn (Order $order): array => $this->orderPayload($order))->values(),
        ]);
    }

    public function show(Request $request, string $id): JsonResponse
    {
        $tenantId = $this->tenantId($request);

        if ($tenantId === null) {
            return $this->missingTenantResponse();
        }

        $order = $this->findTenantOrder($tenantId, $id);

        if ($order === null) {
            return $this->notFoundResponse();
        }

        return response()->json([
            'data' => $this->orderPayload($order),
        ]);
    }

    public function createFromCheckout(Request $request): JsonResponse
    {
        $tenantId = $this->tenantId($request);

        if ($tenantId === null) {
            return $this->missingTenantResponse();
        }

        $validated = $request->validate([
            'checkout_session_id' => ['required', 'uuid'],
        ]);

        $session = CheckoutSession::query()
            ->where('tenant_id', $tenantId)
            ->where('id', $validated['checkout_session_id'])
            ->first();

        if ($session === null) {
            return $this->notFoundResponse('Checkout session not found.');
        }

        try {
            $order = $this->orderService->createFromCheckoutSession($session);
        } catch (ValidationException $exception) {
            return response()->json([
                'message' => $exception->getMessage(),
                'errors' => $exception->errors(),
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        } catch (ModelNotFoundException) {
            return response()->json([
                'message' => 'Checkout cart not found.',
            ], Response::HTTP_NOT_FOUND);
        }

        return response()->json([
            'data' => $this->orderPayload($order),
        ], Response::HTTP_CREATED);
    }

    public function refund(Request $request, string $id): JsonResponse
    {
        $tenantId = $this->tenantId($request);

        if ($tenantId === null) {
            return $this->missingTenantResponse();
        }

        $validated = $request->validate([
            'amount_kobo' => ['nullable', 'integer', 'min:1'],
            'reason' => ['nullable', 'string', 'max:500'],
        ]);

        try {
            $result = $this->refundService->refundOrder(
                $tenantId,
                $id,
                $validated['amount_kobo'] ?? null,
                $validated['reason'] ?? null,
            );
        } catch (ValidationException $exception) {
            return response()->json([
                'message' => collect($exception->errors())->flatten()->first()
                    ?? 'Refund failed.',
                'errors' => $exception->errors(),
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        } catch (ModelNotFoundException) {
            return $this->notFoundResponse();
        }

        $refund = $result['refund'];
        $order = $result['order'];

        return response()->json([
            'data' => [
                'refund' => [
                    'id' => $refund->id,
                    'order_id' => $refund->order_id,
                    'amount_kobo' => $refund->amount_kobo,
                    'currency' => $refund->currency,
                    'status' => $refund->status->value,
                    'gateway_refund_reference' => $refund->gateway_refund_reference,
                ],
                'order' => $this->orderPayload($order),
            ],
        ]);
    }

    private function findTenantOrder(string $tenantId, string $id): ?Order
    {
        return Order::query()
            ->where('tenant_id', $tenantId)
            ->where('id', $id)
            ->with('items')
            ->first();
    }

    /**
     * @return array<string, mixed>
     */
    private function orderPayload(Order $order): array
    {
        return [
            'id' => $order->id,
            'tenant_id' => $order->tenant_id,
            'checkout_session_id' => $order->checkout_session_id,
            'order_number' => $order->order_number,
            'status' => $order->status,
            'currency' => $order->currency,
            'subtotal_kobo' => $order->subtotal_kobo,
            'total_kobo' => $order->total_kobo,
            'customer_email' => $order->customer_email,
            'paystack_reference' => $order->paystack_reference,
            'items' => $order->items->map(static fn ($item): array => [
                'id' => $item->id,
                'product_id' => $item->product_id,
                'product_name' => $item->product_name,
                'quantity' => $item->quantity,
                'unit_price_kobo' => $item->unit_price_kobo,
                'line_total_kobo' => $item->line_total_kobo,
            ])->values()->all(),
            'created_at' => $order->created_at?->toIso8601String(),
            'updated_at' => $order->updated_at?->toIso8601String(),
        ];
    }

    private function notFoundResponse(string $message = 'Order not found.'): JsonResponse
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
