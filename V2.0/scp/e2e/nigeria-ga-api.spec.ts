import { test, expect } from '@playwright/test';
import { randomUUID } from 'node:crypto';

const BASE_URL = (process.env.BASE_URL ?? process.env.SCP_API_URL ?? 'http://localhost:8000').replace(
  /\/$/,
  '',
);

/**
 * Nigeria GA API E2E — Playwright request API (no browser).
 *
 * Mirrors e2e/api/nigeria-ga.test.mjs for teams already using Playwright in CI.
 * Prefer `node api/nigeria-ga.test.mjs` or scripts/run-e2e-api.sh when Playwright is not installed.
 *
 * @see SCP-IMP-021-12 (Launch Readiness Ch. 12)
 */

async function pollProvisioning(
  request: import('@playwright/test').APIRequestContext,
  pollPath: string,
  { timeoutMs = 60_000, intervalMs = 500 } = {},
) {
  const deadline = Date.now() + timeoutMs;
  let lastBody: unknown = null;
  let lastStatus = 0;

  while (Date.now() < deadline) {
    const response = await request.get(`${BASE_URL}${pollPath}`);
    lastStatus = response.status();
    lastBody = await response.json().catch(() => null);

    if (response.ok() && (lastBody as { status?: string })?.status === 'completed') {
      return lastBody;
    }

    if ((lastBody as { status?: string })?.status === 'failed') {
      throw new Error(`Provisioning failed: ${JSON.stringify(lastBody)}`);
    }

    await new Promise((resolve) => setTimeout(resolve, intervalMs));
  }

  throw new Error(
    `Provisioning did not complete within ${timeoutMs}ms (last status ${lastStatus}): ${JSON.stringify(lastBody)}`,
  );
}

test.describe('Nigeria GA — API E2E (request only)', () => {
  test('signup → provisioning → catalog → cart', async ({ request }) => {
    const suffix = randomUUID().slice(0, 8);
    const storeName = `Lagos Tech Shop ${suffix}`;
    const merchantEmail = `nigeria-ga-e2e-${suffix}@example.com`;
    const merchantPassword = 'secure-password-123';
    const sessionId = randomUUID();

    const health = await request.get(`${BASE_URL}/api/health`);
    expect(health.ok()).toBeTruthy();
    await expect(health.json()).resolves.toMatchObject({
      status: 'ok',
      service: 'scp-api',
    });

    const signup = await request.post(`${BASE_URL}/api/v1/signup`, {
      data: {
        email: merchantEmail,
        password: merchantPassword,
        store_name: storeName,
        plan_slug: 'starter',
      },
    });

    expect(signup.status()).toBe(202);
    const signupBody = await signup.json();
    expect(signupBody).toMatchObject({
      status: 'provisioning',
    });
    expect(signupBody.tenant_id).toBeTruthy();
    expect(signupBody.provisioning_run_id).toBeTruthy();
    expect(signupBody.poll_url).toContain(signupBody.tenant_id);

    const tenantId = signupBody.tenant_id as string;
    const pollUrl = signupBody.poll_url as string;

    const provisioning = (await pollProvisioning(request, pollUrl)) as {
      status: string;
      tenant_id: string;
    };
    expect(provisioning.status).toBe('completed');
    expect(provisioning.tenant_id).toBe(tenantId);

    const products = await request.get(`${BASE_URL}/api/v1/commerce/catalog/products`, {
      headers: { 'X-Tenant-ID': tenantId },
    });
    expect(products.ok()).toBeTruthy();
    const productsBody = await products.json();
    expect(productsBody.data).toHaveLength(3);

    const product = productsBody.data[0];
    expect(product.id).toBeTruthy();
    expect(product.price_kobo).toBeGreaterThan(0);

    const addItem = await request.post(`${BASE_URL}/api/v1/commerce/cart/items`, {
      headers: {
        'X-Tenant-ID': tenantId,
        'X-Session-ID': sessionId,
      },
      data: {
        product_id: product.id,
        quantity: 1,
      },
    });
    expect(addItem.status()).toBe(201);

    const cart = await request.get(`${BASE_URL}/api/v1/commerce/cart`, {
      headers: {
        'X-Tenant-ID': tenantId,
        'X-Session-ID': sessionId,
      },
    });
    expect(cart.ok()).toBeTruthy();
    const cartBody = await cart.json();
    expect(cartBody.data.total_kobo).toBe(product.price_kobo);
    expect(cartBody.data.id).toBeTruthy();
  });
});
