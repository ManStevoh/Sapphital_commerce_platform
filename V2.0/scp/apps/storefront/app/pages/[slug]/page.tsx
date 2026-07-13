import type { Metadata } from 'next';
import Link from 'next/link';
import { headers } from 'next/headers';
import { StoreHeader } from '@/components/theme/StoreHeader';
import { loadStorefrontTheme } from '@/lib/theme-loader';

interface ContentPageProps {
  params: Promise<{ slug: string }>;
}

const STATIC_PAGES: Record<string, { title: string; body: string }> = {
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
  return STATIC_PAGES[slug]?.title
    ?? slug
      .split('-')
      .filter(Boolean)
      .map((part) => part.charAt(0).toUpperCase() + part.slice(1))
      .join(' ');
}

export async function generateMetadata({
  params,
}: ContentPageProps): Promise<Metadata> {
  const { slug } = await params;
  const requestHeaders = await headers();
  const storeName = requestHeaders.get('x-tenant-name') ?? 'Store';

  return {
    title: `${titleFromSlug(slug)} — ${storeName}`,
  };
}

export default async function ContentPage({ params }: ContentPageProps) {
  const { slug } = await params;
  const requestHeaders = await headers();
  const tenantSlug = requestHeaders.get('x-tenant-slug');
  const storeName = requestHeaders.get('x-tenant-name') ?? 'Store';
  const themeBundle = await loadStorefrontTheme();
  const page = STATIC_PAGES[slug];
  const title = titleFromSlug(slug);

  return (
    <main style={{ maxWidth: 720, margin: '0 auto', padding: '2rem 1rem' }}>
      <StoreHeader
        storeName={storeName}
        tenantSlug={tenantSlug}
        theme={themeBundle?.config ?? null}
      />

      <p>
        <Link href="/">&larr; Back to shop</Link>
      </p>

      <h1>{title}</h1>
      <p style={{ color: 'var(--color-text-secondary)', marginBottom: '1.5rem' }}>
        Theme template: page
      </p>

      <article style={{ lineHeight: 1.7 }}>
        <p>{page?.body ?? `Content for “${title}” will appear here.`}</p>
      </article>
    </main>
  );
}
