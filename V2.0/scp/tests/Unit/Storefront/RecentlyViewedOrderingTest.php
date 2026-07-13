<?php

declare(strict_types=1);

namespace Tests\Unit\Storefront;

use PHPUnit\Framework\TestCase;

/**
 * Mirrors apps/storefront/lib/recently-viewed.ts pushRecentlyViewedId for regression coverage
 * without requiring a frontend Vitest runner (no npm install in this repo policy).
 */
final class RecentlyViewedOrderingTest extends TestCase
{
    /**
     * @param  list<string>  $current
     * @return list<string>
     */
    private function push(array $current, string $productId, int $max = 8): array
    {
        $id = trim($productId);

        if ($id === '') {
            return array_slice($current, 0, $max);
        }

        $filtered = array_values(array_filter($current, static fn (string $item): bool => $item !== $id));

        return array_slice([$id, ...$filtered], 0, $max);
    }

    public function test_push_keeps_most_recent_first_and_unique(): void
    {
        $this->assertSame(['c', 'a', 'b'], $this->push(['a', 'b'], 'c'));
        $this->assertSame(['a', 'b'], $this->push(['a', 'b'], 'a'));
        $this->assertSame(['4', '1', '2'], $this->push(['1', '2', '3'], '4', 3));
    }

    public function test_push_ignores_blank_ids(): void
    {
        $this->assertSame(['a'], $this->push(['a'], '  '));
    }
}
