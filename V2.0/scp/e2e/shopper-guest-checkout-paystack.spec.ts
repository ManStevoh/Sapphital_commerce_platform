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

test.describe('shopper-guest-checkout-paystack', () => {
  test('guest completes checkout wizard and redirects to Paystack stub', async ({ page }) => {
    const storefrontUp = await isReachable(STOREFRONT_URL);
    test.skip(!storefrontUp, `Storefront not running at ${STOREFRONT_URL}`);

    await page.route('**/api/v1/platform/financial-services/payments/initialize', async (route) => {
      await route.fulfill({
        status: 200,
        contentType: 'application/json',
        body: JSON.stringify({
          data: {
            authorization_url: `${STOREFRONT_URL}/checkout/success?reference=stub_ref_e2e`,
            reference: 'stub_ref_e2e',
          },
        }),
      });
    });

    await page.goto(`${STOREFRONT_URL}/`);

    const productLink = page.getByRole('region', { name: 'Product grid' }).getByRole('link').first();
    test.skip((await productLink.count()) === 0, 'No products in catalog');

    await productLink.click();
    await page.getByRole('button', { name: 'Add to Cart' }).click();
    await expect(page.getByText(/Added to cart — total/)).toBeVisible();

    await page.goto(`${STOREFRONT_URL}/cart`);
    await page.getByRole('link', { name: 'Proceed to checkout' }).click();
    await expect(page.getByRole('heading', { name: 'Checkout' })).toBeVisible();

    await page.getByLabel('Email').fill('guest@example.com');
    await page.getByLabel('Phone (+234)').fill('08012345678');
    await page.getByRole('button', { name: 'Continue to shipping' }).click();

    await page.getByLabel('Address line').fill('12 Marina');
    await page.getByLabel('City').fill('Lagos');

    const shippingRadio = page.getByRole('radio').first();
    if ((await shippingRadio.count()) > 0) {
      await shippingRadio.check();
    }

    await page.getByRole('button', { name: 'Review order' }).click();
    await page.getByRole('button', { name: /Pay .* with Paystack/ }).click();

    await expect(page).toHaveURL(/checkout\/success/, { timeout: 15_000 });
  });
});

test.describe('shopper-payment-failure', () => {
  test('checkout success without reference shows payment issue', async ({ page }) => {
    const reachable = await isReachable(STOREFRONT_URL);
    test.skip(!reachable, `Storefront not running at ${STOREFRONT_URL}`);

    await page.goto(`${STOREFRONT_URL}/checkout/success`);
    await expect(page.getByRole('heading', { name: 'Payment issue' })).toBeVisible();
    await expect(page.getByRole('link', { name: 'Return to cart' })).toBeVisible();
  });
});
