'use client';

import Link from 'next/link';
import { useEffect, useState } from 'react';
import { Button } from '@sapphital/scp-ui';
import { fetchProduct, formatNgn, type Product } from '@/lib/api';
import { resolveClientTenantId } from '@/lib/tenant-client';
import { readWishlistIds, toggleWishlistId } from '@/lib/wishlist';

export default function WishlistPage() {
  const [products, setProducts] = useState<Product[]>([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);

  async function loadWishlist() {
    setLoading(true);
    setError(null);

    try {
      const ids = readWishlistIds();
      await resolveClientTenantId();
      const loaded = await Promise.all(
        ids.map(async (id) => {
          try {
            return await fetchProduct(id);
          } catch {
            return null;
          }
        }),
      );
      setProducts(loaded.filter((item): item is Product => item !== null));
    } catch (err) {
      setError(err instanceof Error ? err.message : 'Failed to load wishlist.');
    } finally {
      setLoading(false);
    }
  }

  useEffect(() => {
    void loadWishlist();
  }, []);

  return (
    <main style={{ maxWidth: 720, margin: '2rem auto', padding: '0 1rem' }}>
      <p>
        <Link href="/">&larr; Shop</Link>
        {' · '}
        <Link href="/account">Account</Link>
      </p>
      <h1>Wishlist</h1>
      <p style={{ color: 'var(--color-text-secondary)' }}>Saved on this device (Phase 2).</p>

      {loading && <p>Loading…</p>}
      {error && <p style={{ color: 'crimson' }}>{error}</p>}

      {!loading && products.length === 0 && <p>Your wishlist is empty.</p>}

      <ul style={{ listStyle: 'none', padding: 0, margin: 0 }}>
        {products.map((product) => (
          <li
            key={product.id}
            style={{
              borderTop: '1px solid var(--color-border, #e5e7eb)',
              padding: '0.75rem 0',
              display: 'flex',
              justifyContent: 'space-between',
              gap: 12,
              alignItems: 'center',
            }}
          >
            <div>
              <Link href={`/products/${product.id}`}>{product.name}</Link>
              <div>{formatNgn(product.price_kobo)}</div>
            </div>
            <Button
              type="button"
              variant="secondary"
              onClick={() => {
                toggleWishlistId(product.id);
                setProducts((current) => current.filter((item) => item.id !== product.id));
              }}
            >
              Remove
            </Button>
          </li>
        ))}
      </ul>
    </main>
  );
}
