<?php

declare(strict_types=1);

namespace Modules\Commerce\Cart\Services;

use Illuminate\Database\Eloquent\ModelNotFoundException;
use Modules\Commerce\Cart\Models\Cart;
use Modules\Commerce\Cart\Models\CartItem;
use Modules\Commerce\Catalog\Models\Product;

final class CartService
{
    public function getOrCreateCart(string $tenantId, string $sessionId): Cart
    {
        $cart = Cart::query()
            ->where('tenant_id', $tenantId)
            ->where('session_id', $sessionId)
            ->first();

        if ($cart !== null) {
            return $cart->load('items');
        }

        $cart = Cart::query()->create([
            'tenant_id' => $tenantId,
            'session_id' => $sessionId,
            'currency' => 'NGN',
        ]);

        return $cart->load('items');
    }

    /**
     * @throws ModelNotFoundException
     */
    public function addItem(Cart $cart, string $productId, int $quantity): CartItem
    {
        $product = Product::query()
            ->where('tenant_id', $cart->tenant_id)
            ->where('id', $productId)
            ->firstOrFail();

        $unitPriceKobo = (int) $product->price_kobo;
        $lineTotalKobo = $unitPriceKobo * $quantity;

        $existingItem = CartItem::query()
            ->where('cart_id', $cart->id)
            ->where('product_id', $productId)
            ->first();

        if ($existingItem !== null) {
            $newQuantity = $existingItem->quantity + $quantity;

            $existingItem->update([
                'quantity' => $newQuantity,
                'unit_price_kobo' => $unitPriceKobo,
                'line_total_kobo' => $unitPriceKobo * $newQuantity,
            ]);

            return $existingItem->refresh();
        }

        return CartItem::query()->create([
            'cart_id' => $cart->id,
            'product_id' => $productId,
            'quantity' => $quantity,
            'unit_price_kobo' => $unitPriceKobo,
            'line_total_kobo' => $lineTotalKobo,
        ]);
    }
}
