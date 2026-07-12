import Link from 'next/link';
import { formatNgn, type Product } from '@/lib/api';

interface ProductGridSectionProps {
  products: Product[];
}

export function ProductGridSection({ products }: ProductGridSectionProps) {
  if (products.length === 0) {
    return <p>No products available yet.</p>;
  }

  return (
    <section aria-label="Product grid">
      <h2 style={{ fontSize: '1.25rem', marginBottom: '1rem' }}>Featured products</h2>
      <ul
        style={{
          listStyle: 'none',
          padding: 0,
          display: 'grid',
          gridTemplateColumns: 'repeat(auto-fill, minmax(220px, 1fr))',
          gap: '1.5rem',
        }}
      >
        {products.map((product) => (
          <li
            key={product.id}
            style={{
              border: '1px solid var(--color-border)',
              borderRadius: 8,
              padding: '1rem',
              background: 'var(--color-surface)',
            }}
          >
            <h3 style={{ fontSize: '1.05rem', margin: '0 0 0.5rem' }}>
              <Link href={`/products/${product.id}`}>{product.name}</Link>
            </h3>
            <p style={{ margin: 0, fontWeight: 600 }}>{formatNgn(product.price_kobo)}</p>
            {product.inventory_qty === 0 && (
              <p style={{ color: 'var(--color-text-muted)', fontSize: '0.875rem' }}>Out of stock</p>
            )}
          </li>
        ))}
      </ul>
    </section>
  );
}
