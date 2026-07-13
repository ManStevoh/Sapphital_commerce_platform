import type { Metadata } from 'next';
import Link from 'next/link';
import { headers } from 'next/headers';
import { notFound } from 'next/navigation';
import { StoreHeader } from '@/components/theme/StoreHeader';
import { CmsSectionRenderer } from '@/components/cms/CmsSectionRenderer';
import { fetchCmsPageBySlug, fetchStoreNavigation } from '@/lib/api';
import { buildCmsMetadata, siteBaseUrl } from '@/lib/seo-metadata';
import { loadStorefrontTheme } from '@/lib/theme-loader';

interface ContentPageProps {
  params: Promise<{ slug: string }>;
}

const STATIC_FALLBACK: Record<string, { title: string; body: string }> = {
  about: {
    title: 'About us',
    body: 'We are a Nigerian merchant powered by SAPPHITAL Commerce Platform.',
  },
  shipping: {
    title: 'Shipping',
    body: 'Orders ship within Lagos in 1–3 business days. Nationwide delivery in 3–7 business days.',
  },
  returns: {
    title: 'Returns',
    body: 'Contact the store within 7 days of delivery for return eligibility.',
  },
  contact: {
    title: 'Contact',
    body: 'Email support@yourstore.test or call +234 800 000 0000.',
  },
};

function titleFromSlug(slug: string): string {
  return (
    STATIC_FALLBACK[slug]?.title ??
    slug
      .split('-')
      .filter(Boolean)
      .map((part) => part.charAt(0).toUpperCase() + part.slice(1))
      .join(' ')
  );
}

export async function generateMetadata({
  params,
}: ContentPageProps): Promise<Metadata> {
  const { slug } = await params;
  const requestHeaders = await headers();
  const storeName = requestHeaders.get('x-tenant-name') ?? 'Store';
  const tenantSlug = requestHeaders.get('x-tenant-slug') ?? undefined;
  const cmsPage = await fetchCmsPageBySlug(slug, tenantSlug);

  return buildCmsMetadata({
    title: cmsPage?.title ?? titleFromSlug(slug),
    slug,
    storeName,
    tenantSlug,
    pathPrefix: '/pages',
    description: cmsPage?.seo_description,
    seo_title: cmsPage?.seo_title,
    seo_description: cmsPage?.seo_description,
    seo_og_image_url: cmsPage?.seo_og_image_url,
    seo_canonical_url: cmsPage?.seo_canonical_url,
  });
}

export default async function ContentPage({ params }: ContentPageProps) {
  const { slug } = await params;
  const requestHeaders = await headers();
  const tenantSlug = requestHeaders.get('x-tenant-slug');
  const storeName = requestHeaders.get('x-tenant-name') ?? 'Store';
  const themeBundle = await loadStorefrontTheme();
  const [cmsPage, navLinks] = await Promise.all([
    fetchCmsPageBySlug(slug, tenantSlug ?? undefined),
    fetchStoreNavigation('header', tenantSlug ?? undefined),
  ]);
  const fallback = STATIC_FALLBACK[slug];

  if (!cmsPage && !fallback) {
    notFound();
  }

  const title = cmsPage?.title ?? titleFromSlug(slug);
  const sections = cmsPage?.body_json?.sections ?? [];
  const hasRenderableSections = sections.length > 0;
  const baseUrl = siteBaseUrl(tenantSlug);
  const pageUrl = cmsPage?.seo_canonical_url ?? `${baseUrl}/pages/${slug}`;
  const breadcrumbJsonLd = {
    '@context': 'https://schema.org',
    '@type': 'BreadcrumbList',
    itemListElement: [
      {
        '@type': 'ListItem',
        position: 1,
        name: storeName,
        item: baseUrl,
      },
      {
        '@type': 'ListItem',
        position: 2,
        name: title,
        item: pageUrl,
      },
    ],
  };

  return (
    <main style={{ maxWidth: 720, margin: '0 auto', padding: '2rem 1rem' }}>
      <script
        type="application/ld+json"
        dangerouslySetInnerHTML={{ __html: JSON.stringify(breadcrumbJsonLd) }}
      />

      <StoreHeader
        storeName={storeName}
        tenantSlug={tenantSlug}
        theme={themeBundle?.config ?? null}
        navLinks={navLinks}
      />

      <nav aria-label="Breadcrumb" style={{ fontSize: '0.875rem', marginBottom: '1rem' }}>
        <Link href="/">Shop</Link>
        {' / '}
        <span>{title}</span>
      </nav>

      <h1>{title}</h1>

      <article style={{ marginTop: '1.5rem' }}>
        {hasRenderableSections ? (
          <CmsSectionRenderer sections={sections} />
        ) : (
          <p style={{ lineHeight: 1.7, whiteSpace: 'pre-wrap' }}>{fallback?.body ?? ''}</p>
        )}
      </article>
    </main>
  );
}
