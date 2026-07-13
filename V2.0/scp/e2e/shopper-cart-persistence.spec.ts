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

test.describe('shopper-cart-persistence', () => {
  test('cart survives page reload via localStorage session', async ({ page }) => {
    const reachable = await isReachable(STOREFRONT_URL);
    test.skip(!reachable, `Storefront not running at ${STOREFRONT_URL}`);

    await page.goto(`${STOREFRONT_URL}/`);

    const productLink = page.getByRole('region', { name: 'Product grid' }).getByRole('link').first();
    const productCount = await productLink.count();
    test.skip(productCount === 0, 'No products in catalog');

    await productLink.click();
    await page.getByRole('button', { name: 'Add to Cart' }).click();
    await expect(page.getByText(/Added to cart — total/)).toBeVisible();

    const sessionId = await page.evaluate(() =>
      localStorage.getItem('scp_storefront_session_id'),
    );
    expect(sessionId).toBeTruthy();

    await page.goto(`${STOREFRONT_URL}/cart`);
    await expect(page.getByRole('heading', { name: 'Your cart' })).toBeVisible();
    await expect(page.getByText('Your cart is empty.')).not.toBeVisible();

    await page.reload();
    await expect(page.getByText('Your cart is empty.')).not.toBeVisible();
  });
});
