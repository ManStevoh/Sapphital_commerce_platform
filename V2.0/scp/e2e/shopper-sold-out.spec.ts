import { test, expect } from '@playwright/test';

const STOREFRONT_URL = process.env.STOREFRONT_URL ?? 'http://localhost:3000';

async function isReachable(url: string): Promise<boolean> {
  try {
    const response = await fetch(url, { method: 'GET' });
    return response.ok;
  } catch {
    return false;
  }
}

test.describe('shopper-sold-out', () => {
  test('sold-out product disables add to cart on PDP', async ({ page }) => {
    const reachable = await isReachable(STOREFRONT_URL);
    test.skip(!reachable, `Storefront not running at ${STOREFRONT_URL}`);

    await page.goto(`${STOREFRONT_URL}/`);

    const outOfStockInGrid = page
      .getByRole('region', { name: 'Product grid' })
      .getByText('Out of stock')
      .first();
    const hasSoldOut = (await outOfStockInGrid.count()) > 0;

    test.skip(!hasSoldOut, 'No sold-out products in seeded catalog');

    await outOfStockInGrid.locator('..').getByRole('link').first().click();

    await expect(page.getByText('Out of stock')).toBeVisible();
    await expect(page.getByRole('button', { name: 'Add to Cart' })).toBeDisabled();
  });
});
