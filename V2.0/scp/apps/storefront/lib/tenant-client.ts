import { resolveTenantBySlug } from '@/lib/api';

export async function resolveClientTenantId(): Promise<string> {
  const envTenantId = process.env.NEXT_PUBLIC_TENANT_ID;

  if (envTenantId) {
    return envTenantId;
  }

  const tenantIdFromDom =
    typeof document !== 'undefined'
      ? document.documentElement.dataset.tenantId
      : undefined;

  if (tenantIdFromDom) {
    return tenantIdFromDom;
  }

  const tenantSlug =
    typeof document !== 'undefined'
      ? document.documentElement.dataset.tenantSlug
      : undefined;

  if (tenantSlug) {
    const tenant = await resolveTenantBySlug(tenantSlug);
    return tenant.id;
  }

  throw new Error(
    'Tenant context not resolved. Set NEXT_PUBLIC_TENANT_ID or use a tenant subdomain.',
  );
}

export async function resolveClientStoreName(): Promise<string> {
  const tenantSlug =
    typeof document !== 'undefined'
      ? document.documentElement.dataset.tenantSlug
      : undefined;

  if (tenantSlug) {
    const tenant = await resolveTenantBySlug(tenantSlug);
    return tenant.name;
  }

  return 'Store';
}
