<?php

declare(strict_types=1);

namespace Modules\Commerce\Orders\Services;

use Modules\Commerce\Catalog\Models\Product;
use Modules\Commerce\Orders\Models\ReturnRequest;

final class InventoryRestockService
{
    public function restockApprovedReturn(ReturnRequest $returnRequest): void
    {
        $returnRequest->loadMissing(['lines.orderItem']);

        foreach ($returnRequest->lines as $line) {
            if (! $line->restock) {
                continue;
            }

            $orderItem = $line->orderItem;

            if ($orderItem === null || $orderItem->product_id === null) {
                continue;
            }

            Product::query()
                ->where('id', $orderItem->product_id)
                ->increment('inventory_qty', $line->quantity);
        }
    }
}
