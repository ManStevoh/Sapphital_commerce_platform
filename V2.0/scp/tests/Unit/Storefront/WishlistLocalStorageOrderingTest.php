<?php

declare(strict_types=1);

namespace Tests\Unit\Storefront;

use PHPUnit\Framework\TestCase;

/**
 * Mirrors apps/storefront/lib/wishlist.ts toggle semantics for regression safety.
 */
final class WishlistLocalStorageOrderingTest extends TestCase
{
    public function test_toggle_adds_then_removes_product_id(): void
    {
        $ids = [];

        $ids = $this->toggle($ids, 'prod-a');
        $this->assertSame(['prod-a'], $ids);

        $ids = $this->toggle($ids, 'prod-b');
        $this->assertSame(['prod-b', 'prod-a'], $ids);

        $ids = $this->toggle($ids, 'prod-a');
        $this->assertSame(['prod-b'], $ids);
    }

    /**
     * @param  list<string>  $ids
     * @return list<string>
     */
    private function toggle(array $ids, string $productId): array
    {
        if (in_array($productId, $ids, true)) {
            return array_values(array_filter($ids, static fn (string $id): bool => $id !== $productId));
        }

        return [$productId, ...$ids];
    }
}
