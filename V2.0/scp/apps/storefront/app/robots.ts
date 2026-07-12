import type { MetadataRoute } from 'next';
import { headers } from 'next/headers';

export default async function robots(): Promise<MetadataRoute.Robots> {
  const requestHeaders = await headers();
  const tenantSlug = requestHeaders.get('x-tenant-slug');

  return {
    rules: {
      userAgent: '*',
      allow: '/',
      disallow: ['/checkout/', '/api/'],
    },
    sitemap: tenantSlug
      ? `https://${tenantSlug}.shops.sapphital.test/sitemap.xml`
      : undefined,
  };
}
