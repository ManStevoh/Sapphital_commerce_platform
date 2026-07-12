import { NextRequest, NextResponse } from 'next/server';

const SHOPS_DOMAIN_SUFFIX = '.shops.sapphital.test';
const API_URL = process.env.NEXT_PUBLIC_API_URL ?? 'http://localhost:8000';

interface TenantLookup {
  id: string;
  slug: string;
  name: string;
  status: string;
}

function extractSlugFromHost(host: string): string | null {
  const hostname = host.split(':')[0].toLowerCase();

  if (!hostname.endsWith(SHOPS_DOMAIN_SUFFIX)) {
    return null;
  }

  const subdomain = hostname.slice(0, -SHOPS_DOMAIN_SUFFIX.length);
  const slug = subdomain.split('.').pop();

  if (!slug || slug === 'www') {
    return null;
  }

  return slug;
}

async function lookupTenant(slug: string): Promise<TenantLookup | 'not_found' | 'error'> {
  try {
    const response = await fetch(
      `${API_URL}/api/v1/platform/tenancy/tenants/by-slug/${encodeURIComponent(slug)}`,
      {
        headers: { Accept: 'application/json' },
        cache: 'no-store',
      },
    );

    if (response.status === 404) {
      return 'not_found';
    }

    if (!response.ok) {
      return 'error';
    }

    return (await response.json()) as TenantLookup;
  } catch {
    return 'error';
  }
}

const UNAVAILABLE_STATUSES = new Set(['suspended', 'churned', 'deleted']);

export async function middleware(request: NextRequest) {
  const pathname = request.nextUrl.pathname;

  if (
    pathname.startsWith('/store-not-found') ||
    pathname.startsWith('/unavailable') ||
    pathname.startsWith('/api/health')
  ) {
    return NextResponse.next();
  }

  const host = request.headers.get('host') ?? '';
  const slug =
    extractSlugFromHost(host) ??
    process.env.NEXT_PUBLIC_DEFAULT_TENANT_SLUG ??
    null;

  if (!slug) {
    return NextResponse.next();
  }

  const tenant = await lookupTenant(slug);

  if (tenant === 'not_found') {
    const url = request.nextUrl.clone();
    url.pathname = '/store-not-found';
    url.searchParams.set('slug', slug);
    return NextResponse.rewrite(url);
  }

  if (tenant === 'error') {
    return NextResponse.next();
  }

  if (UNAVAILABLE_STATUSES.has(tenant.status)) {
    const url = request.nextUrl.clone();
    url.pathname = '/unavailable';
    url.searchParams.set('store', tenant.name);
    return NextResponse.rewrite(url);
  }

  const requestHeaders = new Headers(request.headers);
  requestHeaders.set('x-tenant-slug', tenant.slug);
  requestHeaders.set('x-tenant-id', tenant.id);
  requestHeaders.set('x-tenant-status', tenant.status);
  requestHeaders.set('x-tenant-name', tenant.name);

  return NextResponse.next({
    request: {
      headers: requestHeaders,
    },
  });
}

export const config = {
  matcher: ['/((?!_next/static|_next/image|favicon.ico).*)'],
};
