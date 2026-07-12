'use client';

import Link from 'next/link';
import { formatNgn, type Cart, type ShippingRate } from '@/lib/api';

interface CartSummaryProps {
  cart: Cart;
  shippingRates?: ShippingRate[];
  selectedShippingRateId?: string | null;
  onSelectShippingRate?: (rateId: string) => void;
  showCheckoutLink?: boolean;
  totalOverrideKobo?: number;
}

export function CartSummary({
  cart,
  shippingRates = [],
  selectedShippingRateId = null,
  onSelectShippingRate,
  showCheckoutLink = true,
  totalOverrideKobo,
}: CartSummaryProps) {
  const selectedRate = shippingRates.find((rate) => rate.id === selectedShippingRateId) ?? null;
  const shippingKobo = selectedRate?.price_kobo ?? 0;
  const grandTotalKobo = totalOverrideKobo ?? cart.total_kobo + shippingKobo;
  const hasItems = cart.items.length > 0;

  return (
    <aside
      style={{
        border: '1px solid var(--color-border, #ddd)',
        borderRadius: 8,
        padding: '1.25rem',
        background: 'var(--color-bg-subtle, #fafafa)',
      }}
    >
      <h2 style={{ margin: '0 0 1rem', fontSize: '1.1rem' }}>Order summary</h2>

      <dl style={{ margin: 0, display: 'grid', gap: '0.5rem' }}>
        <div style={{ display: 'flex', justifyContent: 'space-between' }}>
          <dt>Subtotal</dt>
          <dd style={{ margin: 0 }}>{formatNgn(cart.total_kobo)}</dd>
        </div>

        {shippingRates.length > 0 && (
          <div>
            <dt style={{ marginBottom: '0.5rem' }}>Shipping</dt>
            <dd style={{ margin: 0, display: 'grid', gap: '0.5rem' }}>
              {shippingRates.map((rate) => (
                <label
                  key={rate.id}
                  style={{
                    display: 'flex',
                    alignItems: 'center',
                    gap: '0.5rem',
                    cursor: onSelectShippingRate ? 'pointer' : 'default',
                  }}
                >
                  <input
                    type="radio"
                    name="shipping_rate"
                    value={rate.id}
                    checked={selectedShippingRateId === rate.id}
                    onChange={() => onSelectShippingRate?.(rate.id)}
                    disabled={!onSelectShippingRate}
                  />
                  <span>
                    {rate.name} — {rate.is_free_shipping ? 'Free' : formatNgn(rate.price_kobo)}
                  </span>
                </label>
              ))}
            </dd>
          </div>
        )}

        <div
          style={{
            display: 'flex',
            justifyContent: 'space-between',
            fontWeight: 700,
            borderTop: '1px solid var(--color-border, #ddd)',
            paddingTop: '0.75rem',
            marginTop: '0.25rem',
          }}
        >
          <dt>Total</dt>
          <dd style={{ margin: 0 }}>{formatNgn(grandTotalKobo)}</dd>
        </div>
      </dl>

      {showCheckoutLink && hasItems && (
        <p style={{ marginTop: '1.25rem' }}>
          <Link
            href="/checkout"
            style={{
              display: 'inline-block',
              padding: '0.75rem 1.25rem',
              background: 'var(--color-brand, #006644)',
              color: '#fff',
              borderRadius: 6,
              textDecoration: 'none',
              fontWeight: 600,
            }}
          >
            Proceed to checkout
          </Link>
        </p>
      )}

      {!hasItems && <p style={{ marginTop: '1rem', color: '#666' }}>Your cart is empty.</p>}
    </aside>
  );
}
