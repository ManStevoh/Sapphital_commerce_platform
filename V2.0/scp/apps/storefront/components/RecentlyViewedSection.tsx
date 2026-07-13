'use client';

import { useEffect, useMemo, useState } from 'react';
import Link from 'next/link';
import { formatNgn, type Product } from '@/lib/api';
import {
  clearRecentlyViewedCookie,
  readRecentlyViewedCookie,
} from '@/lib/recently-viewed';

interface RecentlyViewedSectionProps {
  products: Product[];
  tenantKey: string;
}

export function RecentlyViewedSection({ products, tenantKey }: RecentlyViewedSectionProps) {
  const [ids, setIds] = useState<string[]>([]);
  const [ready, setReady] = useState(false);

  useEffect(() => {
    setIds(readRecentlyViewedCookie(tenantKey));
    setReady(true);
  }, [tenantKey]);

  const viewed = useMemo(() => {
    const byId = new Map(products.map((product) => [product.id, product]));

    return ids
      .map((id) => byId.get(id))
      .filter((product): product is Product => product !== undefined);
  }, [ids, products]);

  if (!ready || viewed.length === 0) {
    return null;
  }

  return (
    <section aria-label="Recently viewed" style={{ marginTop: '2.5rem' }}>
      <div
        style={{
          display: 'flex',
          justifyContent: 'space-between',
          alignItems: 'baseline',
          gap: '1rem',
          marginBottom: '1rem',
        }}
      >
        <h2 style={{ fontSize: '1.25rem', margin: 0 }}>Recently viewed</h2>
        <button
          type="button"
          onClick={() => {
            clearRecentlyViewedCookie(tenantKey);
            setIds([]);
          }}
          style={{
            background: 'none',
            border: 'none',
            color: 'var(--color-text-secondary)',
            cursor: 'pointer',
            textDecoration: 'underline',
            fontSize: '0.875rem',
          }}
        >
          Clear
        </button>
      </div>
      <ul
        style={{
          listStyle: 'none',
          padding: 0,
          margin: 0,
          display: 'grid',
          gridTemplateColumns: 'repeat(auto-fill, minmax(180px, 1fr))',
          gap: '1rem',
        }}
      >
        {viewed.map((product) => (
          <li
            key={product.id}
            style={{
              borderTop: '1px solid var(--color-border, #e5e7eb)',
              paddingTop: '0.75rem',
            }}
          >
            <Link href={`/products/${product.id}`}>{product.name}</Link>
            <p style={{ margin: '0.25rem 0 0', fontWeight: 600 }}>
              {formatNgn(product.price_kobo)}
            </p>
          </li>
        ))}
      </ul>
    </section>
  );
}
