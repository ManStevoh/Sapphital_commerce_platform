'use client';

import Link from 'next/link';
import { Suspense, useEffect, useState } from 'react';
import { useSearchParams } from 'next/navigation';
import { fetchOrder, formatNgn, verifyPayment } from '@/lib/api';
import { resolveClientTenantId } from '@/lib/tenant-client';

function CheckoutSuccessContent() {
  const searchParams = useSearchParams();
  const reference = searchParams.get('reference') ?? searchParams.get('trxref');
  const [status, setStatus] = useState<'loading' | 'success' | 'error'>('loading');
  const [orderNumber, setOrderNumber] = useState<string | null>(null);
  const [totalKobo, setTotalKobo] = useState<number | null>(null);
  const [error, setError] = useState<string | null>(null);

  useEffect(() => {
    if (!reference) {
      setStatus('error');
      setError('Missing payment reference.');
      return;
    }

    let cancelled = false;

    async function confirmPayment() {
      try {
        const tenantId = await resolveClientTenantId();
        const result = await verifyPayment(reference, tenantId);

        if (cancelled) {
          return;
        }

        if (result.order_id) {
          const order = await fetchOrder(result.order_id, tenantId);
          setOrderNumber(order.order_number);
          setTotalKobo(order.total_kobo);
        }

        setStatus('success');
      } catch (err) {
        if (!cancelled) {
          setStatus('error');
          setError(err instanceof Error ? err.message : 'Payment verification failed.');
        }
      }
    }

    confirmPayment();

    return () => {
      cancelled = true;
    };
  }, [reference]);

  return (
    <main style={{ maxWidth: 560, margin: '3rem auto', padding: '0 1.5rem' }}>
      {status === 'loading' && <p>Confirming your payment…</p>}

      {status === 'success' && (
        <div
          style={{
            border: '1px solid var(--color-border, #e2e8f0)',
            borderRadius: 8,
            padding: '1.5rem',
            background: 'var(--color-brand-subtle, #ecfdf5)',
          }}
        >
          <h1 style={{ marginTop: 0 }}>Order confirmed</h1>
          <p>Thank you — your payment was successful.</p>
          {orderNumber && (
            <p>
              Order number: <strong>{orderNumber}</strong>
            </p>
          )}
          {totalKobo !== null && (
            <p>
              Total paid: <strong>{formatNgn(totalKobo)}</strong>
            </p>
          )}
          <p style={{ color: 'var(--color-text-secondary, #475569)' }}>
            A confirmation email will be sent if you provided one at checkout.
          </p>
          <Link href="/">Continue shopping</Link>
        </div>
      )}

      {status === 'error' && (
        <div>
          <h1>Payment issue</h1>
          <p style={{ color: 'crimson' }}>{error}</p>
          <Link href="/cart">Return to cart</Link>
        </div>
      )}
    </main>
  );
}

export default function CheckoutSuccessPage() {
  return (
    <Suspense fallback={<main style={{ padding: '2rem' }}>Confirming payment…</main>}>
      <CheckoutSuccessContent />
    </Suspense>
  );
}
