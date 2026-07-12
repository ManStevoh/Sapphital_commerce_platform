import type { MetadataRoute } from 'next';
import { headers } from 'next/headers';
import { fetchProducts } from '@/lib/api';

export default async function sitemap(): Promise<MetadataRoute.Sitemap> {
  const requestHeaders = await headers();
  const tenantSlug = requestHeaders.get('x-tenant-slug');
  const baseUrl = tenantSlug
    ? `https://${tenantSlug}.shops.sapphital.test`
    : 'http://localhost:3000';

  const entries: MetadataRoute.Sitemap = [
    {
      url: baseUrl,
      lastModified: new Date(),
      changeFrequency: 'daily',
      priority: 1,
    },
    {
      url: `${baseUrl}/cart`,
      lastModified: new Date(),
      changeFrequency: 'weekly',
      priority: 0.5,
    },
  ];

  try {
    const products = await fetchProducts(tenantSlug ?? undefined);

    for (const product of products) {
      entries.push({
        url: `${baseUrl}/products/${product.id}`,
        lastModified: new Date(),
        changeFrequency: 'weekly',
        priority: 0.8,
      });
    }
  } catch {
    // Tenant unresolved in dev — return base entries only.
  }

  return entries;
}
