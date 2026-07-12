'use client';

import Link from 'next/link';
import { useEffect, useState } from 'react';
import { CartSummary } from '@/components/CartSummary';
import {
  fetchProduct,
  formatNgn,
  getCart,
  getShippingRates,
  type Cart,
  type Product,
  type ShippingRate,
} from '@/lib/api';
import { getSessionId } from '@/lib/session';
import { resolveClientStoreName, resolveClientTenantId } from '@/lib/tenant-client';

interface LineItemView {
  itemId: string;
  productId: string;
  quantity: number;
  lineTotalKobo: number;
  product?: Product;
}

export default function CartPage() {
  const [cart, setCart] = useState<Cart | null>(null);
  const [lineItems, setLineItems] = useState<LineItemView[]>([]);
  const [shippingRates, setShippingRates] = useState<ShippingRate[]>([]);
  const [selectedShippingRateId, setSelectedShippingRateId] = useState<string | null>(null);
  const [storeName, setStoreName] = useState('Store');
  const [status, setStatus] = useState<'loading' | 'ready' | 'error'>('loading');
  const [error, setError] = useState<string | null>(null);

  useEffect(() => {
    let cancelled = false;

    async function loadCart() {
      setStatus('loading');
      setError(null);

      try {
        const sessionId = getSessionId();
        const tenantSlug = document.documentElement.dataset.tenantSlug || undefined;
        const tenantId = await resolveClientTenantId();
        const name = await resolveClientStoreName();

        if (!cancelled) {
          setStoreName(name);
        }

        const cartData = await getCart(sessionId, tenantId);

        if (cancelled) {
          return;
        }

        setCart(cartData);

        const items = await Promise.all(
          cartData.items.map(async (item) => {
            try {
              const product = await fetchProduct(item.product_id, tenantSlug);
              return {
                itemId: item.id,
                productId: item.product_id,
                quantity: item.quantity,
                lineTotalKobo: item.line_total_kobo,
                product,
              };
            } catch {
              return {
                itemId: item.id,
                productId: item.product_id,
                quantity: item.quantity,
                lineTotalKobo: item.line_total_kobo,
              };
            }
          }),
        );

        if (cancelled) {
          return;
        }

        setLineItems(items);

        if (cartData.total_kobo > 0) {
          const rates = await getShippingRates(cartData.total_kobo, tenantId);
          if (!cancelled) {
            setShippingRates(rates);
            setSelectedShippingRateId(rates[0]?.id ?? null);
          }
        }

        setStatus('ready');
      } catch (err) {
        if (!cancelled) {
          setStatus('error');
          setError(err instanceof Error ? err.message : 'Failed to load cart.');
        }
      }
    }

    loadCart();

    return () => {
      cancelled = true;
    };
  }, []);

  return (
    <main style={{ maxWidth: 960, margin: '0 auto', padding: '2rem 1rem' }}>
      <header style={{ marginBottom: '2rem' }}>
        <p>
          <Link href="/">&larr; Back to {storeName}</Link>
        </p>
        <h1 style={{ margin: 0 }}>Your cart</h1>
      </header>

      {status === 'loading' && <p>Loading cart…</p>}
      {status === 'error' && <p style={{ color: 'crimson' }}>{error}</p>}

      {status === 'ready' && cart && (
        <div
          style={{
            display: 'grid',
            gridTemplateColumns: 'minmax(0, 1fr) minmax(260px, 320px)',
            gap: '2rem',
            alignItems: 'start',
          }}
        >
          <section>
            {lineItems.length === 0 && (
              <p>
                Your cart is empty. <Link href="/">Continue shopping</Link>
              </p>
            )}

            {lineItems.length > 0 && (
              <ul style={{ listStyle: 'none', padding: 0, margin: 0, display: 'grid', gap: '1rem' }}>
                {lineItems.map((line) => (
                  <li
                    key={line.itemId}
                    style={{
                      border: '1px solid #ddd',
                      borderRadius: 8,
                      padding: '1rem',
                    }}
                  >
                    <h2 style={{ margin: '0 0 0.5rem', fontSize: '1rem' }}>
                      {line.product?.name ?? `Product ${line.productId.slice(0, 8)}`}
                    </h2>
                    <p style={{ margin: 0, color: '#666' }}>
                      Qty {line.quantity} · {formatNgn(line.lineTotalKobo)}
                    </p>
                  </li>
                ))}
              </ul>
            )}
          </section>

          <CartSummary
            cart={cart}
            shippingRates={shippingRates}
            selectedShippingRateId={selectedShippingRateId}
            onSelectShippingRate={setSelectedShippingRateId}
          />
        </div>
      )}
    </main>
  );
}
