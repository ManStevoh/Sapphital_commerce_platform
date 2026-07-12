import { test, expect } from '@playwright/test';

const MARKETING_URL = process.env.MARKETING_URL ?? 'http://localhost:3003';
const STOREFRONT_URL = process.env.STOREFRONT_URL ?? 'http://localhost:3000';

async function isReachable(url: string): Promise<boolean> {
  try {
    const response = await fetch(url, { method: 'GET' });
    return response.ok;
  } catch {
    return false;
  }
}

test.describe('Nigeria GA — marketing signup funnel', () => {
  test('marketing home and signup pages render', async ({ page }) => {
    const reachable = await isReachable(MARKETING_URL);

    test.skip(!reachable, `Marketing app not running at ${MARKETING_URL}`);

    await page.goto(`${MARKETING_URL}/`);
    await expect(page.getByRole('heading', { name: 'SAPPHITAL', exact: true })).toBeVisible();
    await expect(page.getByRole('heading', { name: /plans/i })).toBeVisible();

    await page.goto(`${MARKETING_URL}/signup?plan=starter`);
    await expect(page.getByRole('heading', { name: /start your store/i })).toBeVisible();
    await expect(page.getByLabel('Email')).toBeVisible();
    await expect(page.getByLabel('Password')).toBeVisible();
    await expect(page.getByLabel('Store name')).toBeVisible();
  });
});

test.describe('Nigeria GA — storefront purchase', () => {
  test('storefront home renders with cart link', async ({ page }) => {
    const reachable = await isReachable(STOREFRONT_URL);

    test.skip(!reachable, `Storefront app not running at ${STOREFRONT_URL}`);

    await page.goto(`${STOREFRONT_URL}/`);
    await expect(page.getByRole('link', { name: /cart/i })).toBeVisible();
  });
});
