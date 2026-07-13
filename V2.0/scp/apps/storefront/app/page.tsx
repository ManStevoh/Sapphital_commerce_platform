import type { Metadata } from 'next';
import Link from 'next/link';
import { headers } from 'next/headers';
import { fetchProducts, fetchStoreNavigation } from '@/lib/api';
import { HeroSection } from '@/components/theme/HeroSection';
import { ProductGridSection } from '@/components/theme/ProductGridSection';
import { RecentlyViewedSection } from '@/components/RecentlyViewedSection';
import { StoreHeader } from '@/components/theme/StoreHeader';
import { TrustBarSection } from '@/components/theme/TrustBarSection';
import { loadStorefrontTheme } from '@/lib/theme-loader';

export async function generateMetadata(): Promise<Metadata> {
  const requestHeaders = await headers();
  const storeName = requestHeaders.get('x-tenant-name') ?? 'Store';
  const tenantSlug = requestHeaders.get('x-tenant-slug');
  const baseUrl = tenantSlug
    ? `https://${tenantSlug}.shops.sapphital.test`
    : undefined;

  return {
    title: storeName,
    description: `Shop ${storeName} — powered by SAPPHITAL`,
    openGraph: {
      title: storeName,
      description: `Discover products at ${storeName}`,
      type: 'website',
      ...(baseUrl ? { url: baseUrl } : {}),
    },
  };
}

export default async function StorefrontPage() {
  const requestHeaders = await headers();
  const tenantSlug = requestHeaders.get('x-tenant-slug');
  const storeName = requestHeaders.get('x-tenant-name') ?? 'Store';
  const themeBundle = await loadStorefrontTheme();
  const navLinks = await fetchStoreNavigation('header', tenantSlug ?? undefined);

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
        navLinks={navLinks}
      />

      {error && <p style={{ color: 'var(--color-error)' }}>{error}</p>}

      {!error && (
        <>
          <HeroSection storeName={storeName} theme={themeBundle?.config ?? null} />
          <RecentlyViewedSection
            products={products}
            tenantKey={tenantSlug ?? 'store'}
          />
          <ProductGridSection products={products} />
          <TrustBarSection />
        </>
      )}
    </main>
  );
}
