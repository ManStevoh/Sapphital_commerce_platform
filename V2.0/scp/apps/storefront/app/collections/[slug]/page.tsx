import type { Metadata } from 'next';
import Link from 'next/link';
import { headers } from 'next/headers';
import { fetchProducts } from '@/lib/api';
import { ProductGridSection } from '@/components/theme/ProductGridSection';
import { StoreHeader } from '@/components/theme/StoreHeader';
import { loadStorefrontTheme } from '@/lib/theme-loader';

interface CollectionPageProps {
  params: Promise<{ slug: string }>;
}

function titleFromSlug(slug: string): string {
  return slug
    .split('-')
    .filter(Boolean)
    .map((part) => part.charAt(0).toUpperCase() + part.slice(1))
    .join(' ');
}

export async function generateMetadata({
  params,
}: CollectionPageProps): Promise<Metadata> {
  const { slug } = await params;
  const requestHeaders = await headers();
  const storeName = requestHeaders.get('x-tenant-name') ?? 'Store';

  return {
    title: `${titleFromSlug(slug)} — ${storeName}`,
    description: `Browse ${titleFromSlug(slug)} at ${storeName}`,
  };
}

export default async function CollectionPage({ params }: CollectionPageProps) {
  const { slug } = await params;
  const requestHeaders = await headers();
  const tenantSlug = requestHeaders.get('x-tenant-slug');
  const storeName = requestHeaders.get('x-tenant-name') ?? 'Store';
  const themeBundle = await loadStorefrontTheme();
  const collectionTitle = titleFromSlug(slug);

  let products: Awaited<ReturnType<typeof fetchProducts>> = [];
  let error: string | null = null;

  try {
    products = await fetchProducts(tenantSlug ?? undefined);
  } catch (err) {
    error = err instanceof Error ? err.message : 'Failed to load products.';
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

      <h1>{collectionTitle}</h1>
      <p style={{ color: 'var(--color-text-secondary)', marginBottom: '1.5rem' }}>
        Theme template: collection
      </p>

      {error && <p style={{ color: 'var(--color-error)' }}>{error}</p>}

      {!error && <ProductGridSection products={products} />}
    </main>
  );
}
