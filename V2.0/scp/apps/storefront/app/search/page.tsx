import type { Metadata } from 'next';
import Link from 'next/link';
import { headers } from 'next/headers';
import { searchProducts } from '@/lib/api';
import { ProductGridSection } from '@/components/theme/ProductGridSection';
import { SearchAutocomplete } from '@/components/SearchAutocomplete';
import { StoreHeader } from '@/components/theme/StoreHeader';
import { loadStorefrontTheme } from '@/lib/theme-loader';

interface SearchPageProps {
  searchParams: Promise<{
    q?: string;
    min?: string;
    max?: string;
    in_stock?: string;
    type?: string;
  }>;
}

export async function generateMetadata({ searchParams }: SearchPageProps): Promise<Metadata> {
  const params = await searchParams;
  const requestHeaders = await headers();
  const storeName = requestHeaders.get('x-tenant-name') ?? 'Store';
  const q = params.q?.trim();

  return {
    title: q ? `Search “${q}” — ${storeName}` : `Search — ${storeName}`,
  };
}

export default async function SearchPage({ searchParams }: SearchPageProps) {
  const params = await searchParams;
  const requestHeaders = await headers();
  const tenantSlug = requestHeaders.get('x-tenant-slug');
  const storeName = requestHeaders.get('x-tenant-name') ?? 'Store';
  const themeBundle = await loadStorefrontTheme();

  const q = params.q?.trim() ?? '';
  const minPrice = params.min ? Number(params.min) : undefined;
  const maxPrice = params.max ? Number(params.max) : undefined;
  const inStock = params.in_stock === '1' ? true : params.in_stock === '0' ? false : undefined;
  const fulfillmentType =
    params.type === 'digital' || params.type === 'physical' ? params.type : undefined;

  let products: Awaited<ReturnType<typeof searchProducts>>['products'] = [];
  let facets: Awaited<ReturnType<typeof searchProducts>>['facets'] | null = null;
  let error: string | null = null;

  try {
    const result = await searchProducts(
      {
        q: q || undefined,
        minPriceKobo: Number.isFinite(minPrice) ? minPrice : undefined,
        maxPriceKobo: Number.isFinite(maxPrice) ? maxPrice : undefined,
        inStock,
        fulfillmentType,
        limit: 48,
      },
      tenantSlug ?? undefined,
    );
    products = result.products;
    facets = result.facets;
  } catch (err) {
    error = err instanceof Error ? err.message : 'Search failed.';
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

      <h1>Search</h1>

      <form method="get" action="/search" style={{ display: 'grid', gap: '0.75rem', marginBottom: '1.5rem' }}>
        <SearchAutocomplete defaultQuery={q} />
        <div style={{ display: 'flex', flexWrap: 'wrap', gap: '0.75rem' }}>
          <label>
            Min (kobo){' '}
            <input name="min" type="number" min={0} defaultValue={params.min ?? ''} />
          </label>
          <label>
            Max (kobo){' '}
            <input name="max" type="number" min={0} defaultValue={params.max ?? ''} />
          </label>
          <label>
            Stock{' '}
            <select name="in_stock" defaultValue={params.in_stock ?? ''}>
              <option value="">Any</option>
              <option value="1">In stock</option>
              <option value="0">Out of stock</option>
            </select>
          </label>
          <label>
            Type{' '}
            <select name="type" defaultValue={params.type ?? ''}>
              <option value="">Any</option>
              <option value="physical">Physical</option>
              <option value="digital">Digital</option>
            </select>
          </label>
          <button type="submit">Search</button>
        </div>
      </form>

      {facets && (
        <p style={{ color: 'var(--color-text-secondary)', fontSize: '0.9rem' }}>
          Price range in catalog: ₦{(facets.price.min_kobo / 100).toFixed(0)} – ₦
          {(facets.price.max_kobo / 100).toFixed(0)} · In stock: {facets.availability.in_stock} · Out
          of stock: {facets.availability.out_of_stock}
        </p>
      )}

      {error && <p style={{ color: 'var(--color-error)' }}>{error}</p>}

      {!error && <ProductGridSection products={products} />}
    </main>
  );
}
