import type { Metadata } from 'next';
import Link from 'next/link';
import { notFound } from 'next/navigation';
import { headers } from 'next/headers';
import { AddToCartButton } from '@/components/AddToCartButton';
import { fetchProduct, fetchRelatedProducts, formatNgn } from '@/lib/api';

interface ProductDetailPageProps {
  params: Promise<{ id: string }>;
}

export async function generateMetadata({
  params,
}: ProductDetailPageProps): Promise<Metadata> {
  const { id } = await params;
  const requestHeaders = await headers();
  const tenantSlug = requestHeaders.get('x-tenant-slug');

  try {
    const product = await fetchProduct(id, tenantSlug ?? undefined);

    return {
      title: product.name,
      description: `Buy ${product.name} for ${formatNgn(product.price_kobo)}`,
      openGraph: {
        title: product.name,
        description: `Shop ${product.name} on SAPPHITAL`,
        type: 'website',
      },
    };
  } catch {
    return { title: 'Product not found' };
  }
}

export default async function ProductDetailPage({ params }: ProductDetailPageProps) {
  const { id } = await params;
  const requestHeaders = await headers();
  const tenantSlug = requestHeaders.get('x-tenant-slug');

  try {
    const product = await fetchProduct(id, tenantSlug ?? undefined);

    if (product.status !== 'published') {
      notFound();
    }

    const related = await fetchRelatedProducts(product.id, tenantSlug ?? undefined);
    const inStock = product.inventory_qty > 0;
    const jsonLd = {
      '@context': 'https://schema.org',
      '@type': 'Product',
      name: product.name,
      offers: {
        '@type': 'Offer',
        priceCurrency: 'NGN',
        price: (product.price_kobo / 100).toFixed(2),
        availability: inStock
          ? 'https://schema.org/InStock'
          : 'https://schema.org/OutOfStock',
      },
    };

    return (
      <main style={{ maxWidth: 640, margin: '0 auto', padding: '2rem 1rem' }}>
        <script
          type="application/ld+json"
          dangerouslySetInnerHTML={{ __html: JSON.stringify(jsonLd) }}
        />

        <p>
          <Link href="/">&larr; Back to shop</Link>
        </p>

        <h1>{product.name}</h1>
        <p style={{ fontSize: '1.25rem', fontWeight: 600 }}>
          {formatNgn(product.price_kobo)}
        </p>
        <p style={{ color: 'var(--color-text-secondary)' }}>
          {inStock ? `${product.inventory_qty} in stock` : 'Out of stock'}
        </p>

        {product.tags && product.tags.length > 0 && (
          <p style={{ fontSize: '0.875rem', color: 'var(--color-text-muted)' }}>
            {product.tags.map((tag) => `#${tag}`).join(' ')}
          </p>
        )}

        <AddToCartButton
          productId={product.id}
          tenantId={product.tenant_id}
          disabled={!inStock}
        />

        {related.length > 0 && (
          <section style={{ marginTop: '2.5rem' }}>
            <h2>Related products</h2>
            <ul style={{ listStyle: 'none', padding: 0, margin: 0 }}>
              {related.map((item) => (
                <li
                  key={item.id}
                  style={{
                    borderTop: '1px solid var(--color-border, #e5e7eb)',
                    padding: '0.75rem 0',
                    display: 'flex',
                    justifyContent: 'space-between',
                    gap: '1rem',
                  }}
                >
                  <Link href={`/products/${item.id}`}>{item.name}</Link>
                  <span>{formatNgn(item.price_kobo)}</span>
                </li>
              ))}
            </ul>
          </section>
        )}
      </main>
    );
  } catch {
    notFound();
  }
}
