#!/usr/bin/env node
/**
 * Provision a tenant for UI E2E and print slug for storefront env.
 * Usage: node e2e/scripts/provision-ui-tenant.mjs
 */
import { randomUUID } from 'node:crypto';

const BASE_URL = (process.env.BASE_URL ?? 'http://127.0.0.1:8000').replace(/\/$/, '');
const suffix = randomUUID().slice(0, 8);
const storeName = `ui-e2e-${suffix}`;
const expectedSlug = storeName;

async function pollProvisioning(pollPath) {
  const deadline = Date.now() + 60_000;

  while (Date.now() < deadline) {
    const response = await fetch(`${BASE_URL}${pollPath}`);
    const body = await response.json().catch(() => ({}));

    if (response.ok && body.status === 'completed') {
      return body;
    }

    if (body.status === 'failed') {
      throw new Error(`Provisioning failed: ${JSON.stringify(body)}`);
    }

    await new Promise((resolve) => setTimeout(resolve, 500));
  }

  throw new Error('Provisioning timed out');
}

const signup = await fetch(`${BASE_URL}/api/v1/signup`, {
  method: 'POST',
  headers: { 'Content-Type': 'application/json', Accept: 'application/json' },
  body: JSON.stringify({
    email: `${storeName}@example.com`,
    password: 'secure-password-123',
    store_name: storeName,
    plan_slug: 'starter',
  }),
});

if (signup.status !== 202) {
  const text = await signup.text();
  throw new Error(`Signup failed (${signup.status}): ${text}`);
}

const signupBody = await signup.json();
await pollProvisioning(signupBody.poll_url);

const tenantResponse = await fetch(
  `${BASE_URL}/api/v1/platform/tenancy/tenants/by-slug/${encodeURIComponent(expectedSlug)}`,
  { headers: { Accept: 'application/json' } },
);

if (!tenantResponse.ok) {
  const text = await tenantResponse.text();
  throw new Error(`Tenant lookup failed (${tenantResponse.status}): ${text}`);
}

const tenant = await tenantResponse.json();

process.stdout.write(
  JSON.stringify({
    slug: tenant.slug,
    tenant_id: tenant.id,
    email: `${storeName}@example.com`,
  }),
);
