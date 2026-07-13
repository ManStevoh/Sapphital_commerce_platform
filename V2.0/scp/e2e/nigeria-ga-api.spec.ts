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
  test('signup → provisioning → catalog → cart → checkout → paid order', async ({ request }) => {
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
    expect(signupBody.admin_handoff_token).toBeTruthy();
    expect(signupBody.email).toBe(merchantEmail);

    const tenantId = signupBody.tenant_id as string;
    const pollUrl = signupBody.poll_url as string;
    const handoffToken = signupBody.admin_handoff_token as string;

    const provisioning = (await pollProvisioning(request, pollUrl)) as {
      status: string;
      tenant_id: string;
    };
    expect(provisioning.status).toBe('completed');
    expect(provisioning.tenant_id).toBe(tenantId);

    const handoff = await request.post(`${BASE_URL}/api/v1/auth/merchant/handoff`, {
      data: { handoff_token: handoffToken },
    });
    expect(handoff.ok()).toBeTruthy();
    const handoffBody = await handoff.json();
    expect(handoffBody.tenant_id).toBe(tenantId);
    const merchantToken = handoffBody.token as string;

    const billingSubscription = await request.get(
      `${BASE_URL}/api/v1/platform/billing/subscription`,
      {
        headers: {
          Authorization: `Bearer ${merchantToken}`,
          'X-Tenant-ID': tenantId,
        },
      },
    );
    expect(billingSubscription.ok()).toBeTruthy();
    await expect(billingSubscription.json()).resolves.toMatchObject({
      data: { status: 'trial' },
    });

    const activateBilling = await request.post(
      `${BASE_URL}/api/v1/platform/billing/subscriptions/${tenantId}/activate`,
      {
        headers: {
          Authorization: `Bearer ${merchantToken}`,
          'X-Tenant-ID': tenantId,
          'Content-Type': 'application/json',
        },
        data: { paystack_reference: `saas_e2e_${suffix}` },
      },
    );
    expect(activateBilling.ok()).toBeTruthy();
    await expect(activateBilling.json()).resolves.toMatchObject({
      data: {
        subscription: { status: 'active' },
        invoice: { status: 'paid' },
      },
    });

    const paymentCredentials = await request.get(
      `${BASE_URL}/api/v1/commerce/storefront/settings/payments/credentials`,
      {
        headers: {
          Authorization: `Bearer ${merchantToken}`,
          'X-Tenant-ID': tenantId,
        },
      },
    );
    expect(paymentCredentials.ok()).toBeTruthy();
    await expect(paymentCredentials.json()).resolves.toMatchObject({
      data: {
        paystack: { configured: false },
        flutterwave: { configured: false },
      },
    });

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
    const cartId = cartBody.data.id as string;

    const checkout = await request.post(`${BASE_URL}/api/v1/commerce/checkout/sessions`, {
      headers: {
        'X-Tenant-ID': tenantId,
        'X-Session-ID': sessionId,
        'Idempotency-Key': randomUUID(),
      },
      data: { cart_id: cartId },
    });
    expect(checkout.status()).toBe(201);
    const checkoutBody = await checkout.json();
    expect(checkoutBody.data.session_id).toBeTruthy();
    const checkoutSessionId = checkoutBody.data.session_id as string;

    const initialize = await request.post(
      `${BASE_URL}/api/v1/platform/financial-services/payments/initialize`,
      {
        headers: {
          'X-Tenant-ID': tenantId,
          'Idempotency-Key': randomUUID(),
        },
        data: {
          checkout_session_id: checkoutSessionId,
          email: 'buyer@example.com',
        },
      },
    );
    expect(initialize.ok()).toBeTruthy();
    const initializeBody = await initialize.json();
    expect(initializeBody.data.authorization_url).toBeTruthy();
    expect(initializeBody.data.reference).toBeTruthy();
    const reference = initializeBody.data.reference as string;

    const verify = await request.post(
      `${BASE_URL}/api/v1/platform/financial-services/payments/verify`,
      {
        headers: {
          'X-Tenant-ID': tenantId,
          'Idempotency-Key': randomUUID(),
        },
        data: { reference },
      },
    );
    expect(verify.ok()).toBeTruthy();
    const verifyBody = await verify.json();
    expect(verifyBody.data.status).toBe('completed');
    expect(verifyBody.data.order_id).toBeTruthy();
    const orderId = verifyBody.data.order_id as string;

    const order = await request.get(`${BASE_URL}/api/v1/commerce/orders/${orderId}`, {
      headers: { 'X-Tenant-ID': tenantId },
    });
    expect(order.ok()).toBeTruthy();
    const orderBody = await order.json();
    expect(orderBody.data.status).toBe('paid');
    expect(orderBody.data.items).toHaveLength(1);
    const orderItemId = orderBody.data.items[0].id as string;

    const merchantLogin = await request.post(`${BASE_URL}/api/v1/auth/merchant/login`, {
      data: {
        email: merchantEmail,
        password: merchantPassword,
      },
    });
    expect(merchantLogin.ok()).toBeTruthy();
    const merchantTokenForReturns = (await merchantLogin.json()).token as string;

    const merchantHeaders = {
      Authorization: `Bearer ${merchantTokenForReturns}`,
      'X-Tenant-ID': tenantId,
      'Content-Type': 'application/json',
    };

    const createReturn = await request.post(`${BASE_URL}/api/v1/commerce/returns`, {
      headers: merchantHeaders,
      data: {
        order_id: orderId,
        reason: 'defective',
        lines: [{ order_item_id: orderItemId, quantity: 1 }],
      },
    });
    expect(createReturn.status()).toBe(201);
    const returnBody = await createReturn.json();
    expect(returnBody.data.status).toBe('requested');
    const returnId = returnBody.data.id as string;

    const approveReturn = await request.post(
      `${BASE_URL}/api/v1/commerce/returns/${returnId}/approve`,
      {
        headers: {
          ...merchantHeaders,
          'Idempotency-Key': randomUUID(),
        },
        data: { issue_refund: true },
      },
    );
    expect(approveReturn.ok()).toBeTruthy();
    const approveBody = await approveReturn.json();
    expect(approveBody.data.status).toBe('refunded');
  });

  test('flutterwave checkout provider path', async ({ request }) => {
    const suffix = randomUUID().slice(0, 8);
    const merchantEmail = `nigeria-ga-fw-${suffix}@example.com`;
    const merchantPassword = 'secure-password-123';
    const sessionId = randomUUID();

    const signup = await request.post(`${BASE_URL}/api/v1/signup`, {
      data: {
        email: merchantEmail,
        password: merchantPassword,
        store_name: `Flutterwave Shop ${suffix}`,
        plan_slug: 'starter',
      },
    });
    expect(signup.status()).toBe(202);
    const signupBody = await signup.json();
    const tenantId = signupBody.tenant_id as string;
    const pollUrl = signupBody.poll_url as string;
    const handoffToken = signupBody.admin_handoff_token as string;

    await pollProvisioning(request, pollUrl);

    const handoff = await request.post(`${BASE_URL}/api/v1/auth/merchant/handoff`, {
      data: { handoff_token: handoffToken },
    });
    const merchantToken = (await handoff.json()).token as string;
    const merchantHeaders = {
      Authorization: `Bearer ${merchantToken}`,
      'X-Tenant-ID': tenantId,
      'Content-Type': 'application/json',
    };

    await request.post(`${BASE_URL}/api/v1/platform/billing/subscriptions/${tenantId}/activate`, {
      headers: merchantHeaders,
      data: { paystack_reference: `saas_fw_e2e_${suffix}` },
    });

    await request.put(`${BASE_URL}/api/v1/commerce/storefront/settings/payments`, {
      headers: merchantHeaders,
      data: { payment_provider: 'flutterwave' },
    });

    const checkoutSettings = await request.get(
      `${BASE_URL}/api/v1/commerce/storefront/checkout-settings`,
      { headers: { 'X-Tenant-ID': tenantId } },
    );
    expect(checkoutSettings.ok()).toBeTruthy();
    await expect(checkoutSettings.json()).resolves.toMatchObject({
      data: { payment_provider: 'flutterwave', currency: 'NGN' },
    });

    const products = await request.get(`${BASE_URL}/api/v1/commerce/catalog/products`, {
      headers: { 'X-Tenant-ID': tenantId },
    });
    const product = (await products.json()).data[0];

    await request.post(`${BASE_URL}/api/v1/commerce/cart/items`, {
      headers: { 'X-Tenant-ID': tenantId, 'X-Session-ID': sessionId },
      data: { product_id: product.id, quantity: 1 },
    });

    const cart = await request.get(`${BASE_URL}/api/v1/commerce/cart`, {
      headers: { 'X-Tenant-ID': tenantId, 'X-Session-ID': sessionId },
    });
    const cartId = (await cart.json()).data.id as string;

    const checkout = await request.post(`${BASE_URL}/api/v1/commerce/checkout/sessions`, {
      headers: {
        'X-Tenant-ID': tenantId,
        'X-Session-ID': sessionId,
        'Idempotency-Key': randomUUID(),
      },
      data: { cart_id: cartId },
    });
    const checkoutSessionId = (await checkout.json()).data.session_id as string;

    const initialize = await request.post(
      `${BASE_URL}/api/v1/platform/financial-services/payments/initialize`,
      {
        headers: {
          'X-Tenant-ID': tenantId,
          'Idempotency-Key': randomUUID(),
        },
        data: {
          checkout_session_id: checkoutSessionId,
          email: 'buyer@example.com',
        },
      },
    );
    expect(initialize.ok()).toBeTruthy();
    const initializeBody = await initialize.json();
    expect(initializeBody.data.authorization_url).toContain('flutterwave.com');
    const reference = initializeBody.data.reference as string;

    const verify = await request.post(
      `${BASE_URL}/api/v1/platform/financial-services/payments/verify`,
      {
        headers: {
          'X-Tenant-ID': tenantId,
          'Idempotency-Key': randomUUID(),
        },
        data: { reference },
      },
    );
    expect(verify.ok()).toBeTruthy();
    await expect(verify.json()).resolves.toMatchObject({
      data: { status: 'completed' },
    });
  });

  test('provisioning seeds CMS pages and merchant can publish blog post', async ({ request }) => {
    const suffix = randomUUID().slice(0, 8);
    const merchantEmail = `nigeria-ga-cms-${suffix}@example.com`;
    const merchantPassword = 'secure-password-123';

    const signup = await request.post(`${BASE_URL}/api/v1/signup`, {
      data: {
        email: merchantEmail,
        password: merchantPassword,
        store_name: `CMS Shop ${suffix}`,
        plan_slug: 'starter',
      },
    });
    expect(signup.status()).toBe(202);
    const signupBody = await signup.json();
    const tenantId = signupBody.tenant_id as string;
    const pollUrl = signupBody.poll_url as string;
    const handoffToken = signupBody.admin_handoff_token as string;

    await pollProvisioning(request, pollUrl);

    const cmsHealth = await request.get(`${BASE_URL}/api/v1/content/cms/health`);
    expect(cmsHealth.ok()).toBeTruthy();
    await expect(cmsHealth.json()).resolves.toMatchObject({ package: 'cms' });

    const publishedPages = await request.get(`${BASE_URL}/api/v1/content/cms/pages/published`, {
      headers: { 'X-Tenant-ID': tenantId },
    });
    expect(publishedPages.ok()).toBeTruthy();
    const pages = (await publishedPages.json()).data as Array<{ slug: string }>;
    expect(pages.length).toBeGreaterThanOrEqual(5);
    expect(pages.some((page) => page.slug === 'about')).toBeTruthy();

    const aboutPage = await request.get(`${BASE_URL}/api/v1/content/cms/pages/by-slug/about`, {
      headers: { 'X-Tenant-ID': tenantId },
    });
    expect(aboutPage.ok()).toBeTruthy();

    const handoff = await request.post(`${BASE_URL}/api/v1/auth/merchant/handoff`, {
      data: { handoff_token: handoffToken },
    });
    expect(handoff.ok()).toBeTruthy();
    const merchantToken = (await handoff.json()).token as string;

    const merchantHeaders = {
      Authorization: `Bearer ${merchantToken}`,
      'X-Tenant-ID': tenantId,
      'Content-Type': 'application/json',
    };

    await request.post(`${BASE_URL}/api/v1/platform/billing/subscriptions/${tenantId}/activate`, {
      headers: merchantHeaders,
      data: { paystack_reference: `saas_cms_e2e_${suffix}` },
    });

    const createPost = await request.post(`${BASE_URL}/api/v1/content/cms/blog-posts`, {
      headers: merchantHeaders,
      data: {
        title: 'Launch Story',
        slug: `launch-story-${suffix}`,
        author_name: 'Store Owner',
        excerpt: 'How we opened our shop.',
        tags: ['news'],
        seo_og_image_url: 'https://cdn.example.test/og-launch.jpg',
        seo_canonical_url: `https://shop.example.test/blog/launch-story-${suffix}`,
        status: 'published',
        published_at: new Date().toISOString(),
        body_json: {
          sections: [{ type: 'rich-text', content: 'We are live.' }],
        },
      },
    });
    expect(createPost.status()).toBe(201);

    const publishedPosts = await request.get(`${BASE_URL}/api/v1/content/cms/blog-posts/published`, {
      headers: { 'X-Tenant-ID': tenantId },
    });
    expect(publishedPosts.ok()).toBeTruthy();
    const posts = (await publishedPosts.json()).data as Array<{ slug: string }>;
    expect(posts.some((post) => post.slug === `launch-story-${suffix}`)).toBeTruthy();

    const feed = await request.get(`${BASE_URL}/api/v1/content/cms/blog/feed.xml`, {
      headers: { 'X-Tenant-ID': tenantId },
    });
    expect(feed.ok()).toBeTruthy();
    const feedXml = await feed.text();
    expect(feedXml).toContain('Launch Story');
    expect(feedXml).toContain(`launch-story-${suffix}`);
  });
});
