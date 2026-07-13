/**
 * Nigeria GA API E2E — Node 22+ (no browser, no Playwright).
 *
 * Flow: health → signup → poll provisioning → catalog → cart → checkout → Paystack verify.
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

test('Nigeria GA API E2E — signup through paid order', async () => {
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
  assert.ok(signup.body.admin_handoff_token, 'admin_handoff_token');
  assert.equal(signup.body.email, merchantEmail);

  const tenantId = signup.body.tenant_id;
  const pollUrl = signup.body.poll_url;
  const handoffToken = signup.body.admin_handoff_token;
  assert.match(pollUrl, new RegExp(tenantId));

  // 3. Poll provisioning
  const provisioning = await pollProvisioning(pollUrl);
  assert.equal(provisioning.status, 'completed');
  assert.equal(provisioning.tenant_id, tenantId);

  // 3b. Signup handoff → billing activation
  const handoff = await api('/api/v1/auth/merchant/handoff', {
    method: 'POST',
    body: JSON.stringify({ handoff_token: handoffToken }),
  });
  assert.equal(handoff.status, 200, 'handoff status');
  assert.equal(handoff.body.tenant_id, tenantId, 'handoff tenant_id');
  const merchantToken = handoff.body.token;

  const billingSubscription = await api('/api/v1/platform/billing/subscription', {
    method: 'GET',
    headers: {
      Authorization: `Bearer ${merchantToken}`,
      'X-Tenant-ID': tenantId,
    },
  });
  assert.equal(billingSubscription.status, 200, 'billing subscription status');
  assert.equal(billingSubscription.body.data.status, 'trial', 'trial subscription');

  const activateBilling = await api(
    `/api/v1/platform/billing/subscriptions/${tenantId}/activate`,
    {
      method: 'POST',
      headers: {
        Authorization: `Bearer ${merchantToken}`,
        'X-Tenant-ID': tenantId,
      },
      body: JSON.stringify({ paystack_reference: `saas_e2e_${suffix}` }),
    },
  );
  assert.equal(activateBilling.status, 200, 'activate billing status');
  assert.equal(activateBilling.body.data.subscription.status, 'active', 'active subscription');
  assert.equal(activateBilling.body.data.invoice.status, 'paid', 'paid invoice');

  const paymentCredentials = await api(
    '/api/v1/commerce/storefront/settings/payments/credentials',
    {
      method: 'GET',
      headers: {
        Authorization: `Bearer ${merchantToken}`,
        'X-Tenant-ID': tenantId,
      },
    },
  );
  assert.equal(paymentCredentials.status, 200, 'payment credentials status');
  assert.equal(paymentCredentials.body.data.paystack.configured, false, 'paystack not configured');
  assert.equal(paymentCredentials.body.data.flutterwave.configured, false, 'flutterwave not configured');

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
  const cartId = cart.body.data.id;

  // 6. Checkout session
  const checkout = await api('/api/v1/commerce/checkout/sessions', {
    method: 'POST',
    headers: {
      'X-Tenant-ID': tenantId,
      'X-Session-ID': sessionId,
      'Idempotency-Key': randomUUID(),
    },
    body: JSON.stringify({ cart_id: cartId }),
  });

  assert.equal(checkout.status, 201, 'checkout status');
  assert.ok(checkout.body.data.session_id, 'checkout session id');
  const checkoutSessionId = checkout.body.data.session_id;

  // 7. Paystack initialize
  const initialize = await api('/api/v1/platform/financial-services/payments/initialize', {
    method: 'POST',
    headers: {
      'X-Tenant-ID': tenantId,
      'Idempotency-Key': randomUUID(),
    },
    body: JSON.stringify({
      checkout_session_id: checkoutSessionId,
      email: 'buyer@example.com',
    }),
  });

  assert.equal(initialize.status, 200, 'initialize status');
  assert.ok(initialize.body.data.authorization_url, 'authorization_url');
  assert.ok(initialize.body.data.reference, 'reference');
  const reference = initialize.body.data.reference;

  // 8. Paystack verify (stub mode)
  const verify = await api('/api/v1/platform/financial-services/payments/verify', {
    method: 'POST',
    headers: {
      'X-Tenant-ID': tenantId,
      'Idempotency-Key': randomUUID(),
    },
    body: JSON.stringify({ reference }),
  });

  assert.equal(verify.status, 200, 'verify status');
  assert.equal(verify.body.data.status, 'completed', 'payment status');
  assert.ok(verify.body.data.order_id, 'order_id');
  const orderId = verify.body.data.order_id;

  const order = await api(`/api/v1/commerce/orders/${orderId}`, {
    headers: { 'X-Tenant-ID': tenantId },
  });
  assert.equal(order.status, 200, 'order status');
  assert.equal(order.body.data.status, 'paid', 'order paid');
  assert.equal(order.body.data.items.length, 1, 'order items');
  const orderItemId = order.body.data.items[0].id;

  const merchantLogin = await api('/api/v1/auth/merchant/login', {
    method: 'POST',
    body: JSON.stringify({
      email: merchantEmail,
      password: merchantPassword,
    }),
  });
  assert.equal(merchantLogin.status, 200, 'merchant login');
  const merchantTokenForReturns = merchantLogin.body.token;

  const merchantHeaders = {
    Authorization: `Bearer ${merchantTokenForReturns}`,
    'X-Tenant-ID': tenantId,
    'Content-Type': 'application/json',
  };

  const createReturn = await api('/api/v1/commerce/returns', {
    method: 'POST',
    headers: merchantHeaders,
    body: JSON.stringify({
      order_id: orderId,
      reason: 'defective',
      lines: [{ order_item_id: orderItemId, quantity: 1 }],
    }),
  });
  assert.equal(createReturn.status, 201, 'create return');
  assert.equal(createReturn.body.data.status, 'requested', 'return requested');
  const returnId = createReturn.body.data.id;

  const approveReturn = await api(`/api/v1/commerce/returns/${returnId}/approve`, {
    method: 'POST',
    headers: {
      ...merchantHeaders,
      'Idempotency-Key': randomUUID(),
    },
    body: JSON.stringify({ issue_refund: true }),
  });
  assert.equal(approveReturn.status, 200, 'approve return');
  assert.equal(approveReturn.body.data.status, 'refunded', 'return refunded');
});

test('Nigeria GA API E2E — Flutterwave checkout provider', async () => {
  const suffix = randomUUID().slice(0, 8);
  const merchantEmail = `nigeria-ga-fw-${suffix}@example.com`;
  const merchantPassword = 'secure-password-123';
  const sessionId = randomUUID();

  const signup = await api('/api/v1/signup', {
    method: 'POST',
    body: JSON.stringify({
      email: merchantEmail,
      password: merchantPassword,
      store_name: `Flutterwave Shop ${suffix}`,
      plan_slug: 'starter',
    }),
  });
  assert.equal(signup.status, 202, 'signup status');
  const tenantId = signup.body.tenant_id;
  await pollProvisioning(signup.body.poll_url);

  const handoff = await api('/api/v1/auth/merchant/handoff', {
    method: 'POST',
    body: JSON.stringify({ handoff_token: signup.body.admin_handoff_token }),
  });
  const merchantToken = handoff.body.token;
  const merchantHeaders = {
    Authorization: `Bearer ${merchantToken}`,
    'X-Tenant-ID': tenantId,
    'Content-Type': 'application/json',
  };

  await api(`/api/v1/platform/billing/subscriptions/${tenantId}/activate`, {
    method: 'POST',
    headers: merchantHeaders,
    body: JSON.stringify({ paystack_reference: `saas_fw_e2e_${suffix}` }),
  });

  await api('/api/v1/commerce/storefront/settings/payments', {
    method: 'PUT',
    headers: merchantHeaders,
    body: JSON.stringify({ payment_provider: 'flutterwave' }),
  });

  const checkoutSettings = await api('/api/v1/commerce/storefront/checkout-settings', {
    method: 'GET',
    headers: { 'X-Tenant-ID': tenantId },
  });
  assert.equal(checkoutSettings.body.data.payment_provider, 'flutterwave', 'flutterwave provider');

  const products = await api('/api/v1/commerce/catalog/products', {
    method: 'GET',
    headers: { 'X-Tenant-ID': tenantId },
  });
  const product = products.body.data[0];

  await api('/api/v1/commerce/cart/items', {
    method: 'POST',
    headers: { 'X-Tenant-ID': tenantId, 'X-Session-ID': sessionId },
    body: JSON.stringify({ product_id: product.id, quantity: 1 }),
  });

  const cart = await api('/api/v1/commerce/cart', {
    method: 'GET',
    headers: { 'X-Tenant-ID': tenantId, 'X-Session-ID': sessionId },
  });
  const cartId = cart.body.data.id;

  const checkout = await api('/api/v1/commerce/checkout/sessions', {
    method: 'POST',
    headers: {
      'X-Tenant-ID': tenantId,
      'X-Session-ID': sessionId,
      'Idempotency-Key': randomUUID(),
    },
    body: JSON.stringify({ cart_id: cartId }),
  });
  const checkoutSessionId = checkout.body.data.session_id;

  const initialize = await api('/api/v1/platform/financial-services/payments/initialize', {
    method: 'POST',
    headers: {
      'X-Tenant-ID': tenantId,
      'Idempotency-Key': randomUUID(),
    },
    body: JSON.stringify({
      checkout_session_id: checkoutSessionId,
      email: 'buyer@example.com',
    }),
  });
  assert.ok(
    String(initialize.body.data.authorization_url).includes('flutterwave.com'),
    'flutterwave authorization_url',
  );
  const reference = initialize.body.data.reference;

  const verify = await api('/api/v1/platform/financial-services/payments/verify', {
    method: 'POST',
    headers: {
      'X-Tenant-ID': tenantId,
      'Idempotency-Key': randomUUID(),
    },
    body: JSON.stringify({ reference }),
  });
  assert.equal(verify.body.data.status, 'completed', 'flutterwave payment completed');
});
