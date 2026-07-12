<?php

declare(strict_types=1);

namespace Modules\Commerce\Cart\Http\Controllers;

use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Commerce\Cart\Models\Cart;
use Modules\Commerce\Cart\Services\CartService;
use Symfony\Component\HttpFoundation\Response;

final class CartController
{
    public function __construct(
        private readonly CartService $cartService,
    ) {}

    public function show(Request $request): JsonResponse
    {
        $tenantId = $this->tenantId($request);

        if ($tenantId === null) {
            return $this->missingTenantResponse();
        }

        $sessionId = $request->header('X-Session-ID');

        if (! is_string($sessionId) || $sessionId === '') {
            return response()->json([
                'message' => 'X-Session-ID header required.',
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $cart = $this->cartService->getOrCreateCart($tenantId, $sessionId);

        return response()->json([
            'data' => $this->formatCart($cart),
        ]);
    }

    public function addItem(Request $request): JsonResponse
    {
        $tenantId = $this->tenantId($request);

        if ($tenantId === null) {
            return $this->missingTenantResponse();
        }

        $sessionId = $request->header('X-Session-ID');

        if (! is_string($sessionId) || $sessionId === '') {
            return response()->json([
                'message' => 'X-Session-ID header required.',
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $validated = $request->validate([
            'product_id' => ['required', 'uuid'],
            'quantity' => ['required', 'integer', 'min:1'],
        ]);

        $cart = $this->cartService->getOrCreateCart($tenantId, $sessionId);

        try {
            $item = $this->cartService->addItem(
                $cart,
                $validated['product_id'],
                (int) $validated['quantity'],
            );
        } catch (ModelNotFoundException) {
            return response()->json([
                'message' => 'Product not found.',
            ], Response::HTTP_NOT_FOUND);
        }

        $cart->refresh()->load('items');

        return response()->json([
            'data' => [
                'cart' => $this->formatCart($cart),
                'item' => $item->only([
                    'id',
                    'cart_id',
                    'product_id',
                    'quantity',
                    'unit_price_kobo',
                    'line_total_kobo',
                ]),
            ],
        ], Response::HTTP_CREATED);
    }

    /**
     * @return array<string, mixed>
     */
    private function formatCart(Cart $cart): array
    {
        $items = $cart->items->map(static fn ($item) => $item->only([
            'id',
            'product_id',
            'quantity',
            'unit_price_kobo',
            'line_total_kobo',
        ]))->values()->all();

        return [
            'id' => $cart->id,
            'tenant_id' => $cart->tenant_id,
            'session_id' => $cart->session_id,
            'currency' => $cart->currency,
            'items' => $items,
            'total_kobo' => $cart->items->sum('line_total_kobo'),
        ];
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
