/**
 * Nigeria GA API E2E — Node 22+ (no browser, no Playwright).
 *
 * Flow: health → signup → poll provisioning → catalog → cart.
 * Complements tests/Feature/Launch/NigeriaGaFlowTest.php for live localhost:8000.
 *
 * @see SCP-IMP-021-12 (Launch Readiness Ch. 12)
 */

import assert from 'node:assert/strict';
import { randomUUID } from 'node:crypto';
import { test } from 'node:test';

const BASE_URL = (process.env.BASE_URL ?? 'http://localhost:8000').replace(/\/$/, '');

/** @param {string} path */
function url(path) {
  return `${BASE_URL}${path.startsWith('/') ? path : `/${path}`}`;
}

/**
 * @param {string} path
 * @param {RequestInit & { headers?: Record<string, string> }} [options]
 */
async function api(path, options = {}) {
  const response = await fetch(url(path), {
    ...options,
    headers: {
      Accept: 'application/json',
      'Content-Type': 'application/json',
      ...options.headers,
    },
  });

  const contentType = response.headers.get('content-type') ?? '';
  const body = contentType.includes('application/json')
    ? await response.json()
    : await response.text();

  return { response, body, status: response.status, ok: response.ok };
}

/** @param {string} pollPath */
async function pollProvisioning(pollPath, { timeoutMs = 60_000, intervalMs = 500 } = {}) {
  const deadline = Date.now() + timeoutMs;
  let lastBody = null;
  let lastStatus = 0;

  while (Date.now() < deadline) {
    const { response, body, status } = await api(pollPath, { method: 'GET' });
    lastBody = body;
    lastStatus = status;

    if (response.ok && body?.status === 'completed') {
      return body;
    }

    if (body?.status === 'failed') {
      throw new Error(`Provisioning failed: ${JSON.stringify(body)}`);
    }

    await new Promise((resolve) => setTimeout(resolve, intervalMs));
  }

  throw new Error(
    `Provisioning did not complete within ${timeoutMs}ms (last status ${lastStatus}): ${JSON.stringify(lastBody)}`,
  );
}

test('Nigeria GA API E2E — signup through cart', async () => {
  const suffix = randomUUID().slice(0, 8);
  const storeName = `Lagos Tech Shop ${suffix}`;
  const merchantEmail = `nigeria-ga-e2e-${suffix}@example.com`;
  const merchantPassword = 'secure-password-123';
  const sessionId = randomUUID();

  // 1. Health
  const health = await api('/api/health', { method: 'GET' });
  assert.equal(health.status, 200, 'health status');
  assert.deepEqual(health.body, { status: 'ok', service: 'scp-api' });

  // 2. Signup
  const signup = await api('/api/v1/signup', {
    method: 'POST',
    body: JSON.stringify({
      email: merchantEmail,
      password: merchantPassword,
      store_name: storeName,
      plan_slug: 'starter',
    }),
  });

  assert.equal(signup.status, 202, 'signup status');
  assert.equal(signup.body.status, 'provisioning');
  assert.ok(signup.body.tenant_id, 'tenant_id');
  assert.ok(signup.body.provisioning_run_id, 'provisioning_run_id');
  assert.ok(signup.body.poll_url, 'poll_url');

  const tenantId = signup.body.tenant_id;
  const pollUrl = signup.body.poll_url;
  assert.match(pollUrl, new RegExp(tenantId));

  // 3. Poll provisioning
  const provisioning = await pollProvisioning(pollUrl);
  assert.equal(provisioning.status, 'completed');
  assert.equal(provisioning.tenant_id, tenantId);

  // 4. Catalog — 3 seeded sample products
  const products = await api('/api/v1/commerce/catalog/products', {
    method: 'GET',
    headers: { 'X-Tenant-ID': tenantId },
  });

  assert.equal(products.status, 200, 'products status');
  assert.ok(Array.isArray(products.body.data), 'products data array');
  assert.equal(products.body.data.length, 3, 'seeded product count');

  const product = products.body.data[0];
  assert.ok(product.id, 'product id');
  assert.ok(product.price_kobo > 0, 'product price_kobo');

  // 5. Cart flow — add item and verify total
  const addItem = await api('/api/v1/commerce/cart/items', {
    method: 'POST',
    headers: {
      'X-Tenant-ID': tenantId,
      'X-Session-ID': sessionId,
    },
    body: JSON.stringify({
      product_id: product.id,
      quantity: 1,
    }),
  });

  assert.equal(addItem.status, 201, 'add to cart status');

  const cart = await api('/api/v1/commerce/cart', {
    method: 'GET',
    headers: {
      'X-Tenant-ID': tenantId,
      'X-Session-ID': sessionId,
    },
  });

  assert.equal(cart.status, 200, 'cart status');
  assert.equal(cart.body.data.total_kobo, product.price_kobo, 'cart total');
  assert.ok(cart.body.data.id, 'cart id');
});
