import Link from 'next/link';
import { headers } from 'next/headers';
import { fetchProducts } from '@/lib/api';
import { HeroSection } from '@/components/theme/HeroSection';
import { ProductGridSection } from '@/components/theme/ProductGridSection';
import { StoreHeader } from '@/components/theme/StoreHeader';
import { TrustBarSection } from '@/components/theme/TrustBarSection';
import { loadStorefrontTheme } from '@/lib/theme-loader';

export default async function StorefrontPage() {
  const requestHeaders = await headers();
  const tenantSlug = requestHeaders.get('x-tenant-slug');
  const storeName = requestHeaders.get('x-tenant-name') ?? 'Store';
  const themeBundle = await loadStorefrontTheme();

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

      {error && <p style={{ color: 'var(--color-error)' }}>{error}</p>}

      {!error && (
        <>
          <HeroSection storeName={storeName} theme={themeBundle?.config ?? null} />
          <ProductGridSection products={products} />
          <TrustBarSection />
        </>
      )}
    </main>
  );
}
