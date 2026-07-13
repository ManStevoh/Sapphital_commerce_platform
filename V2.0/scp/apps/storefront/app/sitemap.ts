import type { MetadataRoute } from 'next';
import { headers } from 'next/headers';
import { fetchProducts, fetchPublishedBlogPosts, fetchPublishedCmsPages } from '@/lib/api';

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
    {
      url: `${baseUrl}/blog`,
      lastModified: new Date(),
      changeFrequency: 'daily',
      priority: 0.7,
    },
  ];

  try {
    const [products, cmsPages, blogPosts] = await Promise.all([
      fetchProducts(tenantSlug ?? undefined),
      fetchPublishedCmsPages(tenantSlug ?? undefined),
      fetchPublishedBlogPosts(tenantSlug ?? undefined),
    ]);

    for (const product of products) {
      entries.push({
        url: `${baseUrl}/products/${product.id}`,
        lastModified: new Date(),
        changeFrequency: 'weekly',
        priority: 0.8,
      });
    }

    for (const page of cmsPages) {
      entries.push({
        url: `${baseUrl}/pages/${page.slug}`,
        lastModified: page.updated_at ? new Date(page.updated_at) : new Date(),
        changeFrequency: 'monthly',
        priority: 0.6,
      });
    }

    for (const post of blogPosts) {
      entries.push({
        url: `${baseUrl}/blog/${post.slug}`,
        lastModified: post.published_at ? new Date(post.published_at) : new Date(),
        changeFrequency: 'weekly',
        priority: 0.7,
      });
    }
  } catch {
    // Tenant unresolved in dev — return base entries only.
  }

  return entries;
}
