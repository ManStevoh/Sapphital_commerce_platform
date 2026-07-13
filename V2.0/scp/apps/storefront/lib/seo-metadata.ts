import type { Metadata } from 'next';

export function siteBaseUrl(tenantSlug?: string | null): string {
  return tenantSlug
    ? `https://${tenantSlug}.shops.sapphital.test`
    : 'http://localhost:3000';
}

export interface CmsSeoInput {
  title: string;
  slug: string;
  storeName: string;
  tenantSlug?: string | null;
  pathPrefix: '/pages' | '/blog';
  description?: string | null;
  seo_title?: string | null;
  seo_description?: string | null;
  seo_og_image_url?: string | null;
  seo_canonical_url?: string | null;
  fallbackOgImage?: string | null;
}

export function buildCmsMetadata(input: CmsSeoInput): Metadata {
  const baseUrl = siteBaseUrl(input.tenantSlug);
  const pageTitle = input.seo_title ?? input.title;
  const description = input.seo_description ?? input.description ?? undefined;
  const canonical = input.seo_canonical_url ?? `${baseUrl}${input.pathPrefix}/${input.slug}`;
  const ogImage = input.seo_og_image_url ?? input.fallbackOgImage ?? undefined;

  return {
    title: `${pageTitle} — ${input.storeName}`,
    description,
    alternates: {
      canonical,
    },
    openGraph: {
      title: pageTitle,
      description,
      url: canonical,
      type: input.pathPrefix === '/blog' ? 'article' : 'website',
      images: ogImage ? [{ url: ogImage }] : undefined,
    },
  };
}
