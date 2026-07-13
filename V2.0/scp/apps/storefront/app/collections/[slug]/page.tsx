import type { Metadata } from 'next';
import Link from 'next/link';
import { headers } from 'next/headers';
import { notFound } from 'next/navigation';
import { fetchCollectionBySlug } from '@/lib/api';
import { ProductGridSection } from '@/components/theme/ProductGridSection';
import { StoreHeader } from '@/components/theme/StoreHeader';
import { loadStorefrontTheme } from '@/lib/theme-loader';

interface CollectionPageProps {
  params: Promise<{ slug: string }>;
}

export async function generateMetadata({
  params,
}: CollectionPageProps): Promise<Metadata> {
  const { slug } = await params;
  const requestHeaders = await headers();
  const storeName = requestHeaders.get('x-tenant-name') ?? 'Store';
  const tenantSlug = requestHeaders.get('x-tenant-slug') ?? undefined;

  const payload = await fetchCollectionBySlug(slug, tenantSlug);

  if (!payload) {
    return {
      title: `Collection — ${storeName}`,
    };
  }

  return {
    title: `${payload.collection.title} — ${storeName}`,
    description:
      payload.collection.description ??
      `Browse ${payload.collection.title} at ${storeName}`,
  };
}

export default async function CollectionPage({ params }: CollectionPageProps) {
  const { slug } = await params;
  const requestHeaders = await headers();
  const tenantSlug = requestHeaders.get('x-tenant-slug');
  const storeName = requestHeaders.get('x-tenant-name') ?? 'Store';
  const themeBundle = await loadStorefrontTheme();

  const payload = await fetchCollectionBySlug(slug, tenantSlug ?? undefined);

  if (!payload) {
    notFound();
  }

  return (
    <main style={{ maxWidth: 960, margin: '0 auto', padding: '2rem 1rem' }}>
      <StoreHeader
        storeName={storeName}
        tenantSlug={tenantSlug}
        theme={themeBundle?.config ?? null}
      />

      <p>
        <Link href="/">&larr; Back to shop</Link>
      </p>

      <h1>{payload.collection.title}</h1>
      {payload.collection.description && (
        <p style={{ color: 'var(--color-text-secondary)', marginBottom: '1.5rem' }}>
          {payload.collection.description}
        </p>
      )}

      {payload.products.length === 0 ? (
        <p>No products in this collection yet.</p>
      ) : (
        <ProductGridSection products={payload.products} />
      )}
    </main>
  );
}
