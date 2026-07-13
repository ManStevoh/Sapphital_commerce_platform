'use client';

import Link from 'next/link';
import { useState } from 'react';
import {
  lookupGuestReturnOrder,
  submitGuestReturnRequest,
  type GuestReturnLookup,
} from '@/lib/api';
import { resolveClientStoreName, resolveClientTenantId } from '@/lib/tenant-client';

type Step = 'lookup' | 'select' | 'done';

export default function ReturnsPage() {
  const [step, setStep] = useState<Step>('lookup');
  const [orderNumber, setOrderNumber] = useState('');
  const [customerEmail, setCustomerEmail] = useState('');
  const [reason, setReason] = useState('');
  const [lookup, setLookup] = useState<GuestReturnLookup | null>(null);
  const [selectedItemId, setSelectedItemId] = useState('');
  const [quantity, setQuantity] = useState(1);
  const [storeName, setStoreName] = useState('Store');
  const [status, setStatus] = useState<'idle' | 'loading' | 'error'>('idle');
  const [error, setError] = useState<string | null>(null);
  const [confirmationId, setConfirmationId] = useState<string | null>(null);

  async function handleLookup(event: React.FormEvent) {
    event.preventDefault();
    setStatus('loading');
    setError(null);

    try {
      const tenantId = await resolveClientTenantId();
      const name = await resolveClientStoreName();
      setStoreName(name);

      const order = await lookupGuestReturnOrder(tenantId, orderNumber.trim(), customerEmail.trim());
      setLookup(order);
      setSelectedItemId(order.items[0]?.id ?? '');
      setQuantity(1);
      setStep('select');
      setStatus('idle');
    } catch (err) {
      setStatus('error');
      setError(err instanceof Error ? err.message : 'Could not find that order.');
    }
  }

  async function handleSubmit(event: React.FormEvent) {
    event.preventDefault();

    if (!lookup || !selectedItemId) {
      return;
    }

    setStatus('loading');
    setError(null);

    try {
      const tenantId = await resolveClientTenantId();
      const result = await submitGuestReturnRequest(tenantId, {
        order_number: orderNumber.trim(),
        customer_email: customerEmail.trim(),
        reason: reason.trim(),
        lines: [{ order_item_id: selectedItemId, quantity }],
      });

      setConfirmationId(result.id);
      setStep('done');
      setStatus('idle');
    } catch (err) {
      setStatus('error');
      setError(err instanceof Error ? err.message : 'Failed to submit return request.');
    }
  }

  return (
    <main style={{ maxWidth: '640px', margin: '0 auto', padding: '2rem 1rem' }}>
      <p>
        <Link href="/">← Back to {storeName}</Link>
      </p>

      <h1>Request a return</h1>
      <p>Look up your order with your order number and checkout email.</p>

      {error && (
        <p role="alert" style={{ color: 'crimson' }}>
          {error}
        </p>
      )}

      {step === 'lookup' && (
        <form onSubmit={handleLookup}>
          <label>
            Order number
            <input
              required
              value={orderNumber}
              onChange={(event) => setOrderNumber(event.target.value)}
              style={{ display: 'block', width: '100%', marginBottom: '1rem' }}
            />
          </label>
          <label>
            Email used at checkout
            <input
              required
              type="email"
              value={customerEmail}
              onChange={(event) => setCustomerEmail(event.target.value)}
              style={{ display: 'block', width: '100%', marginBottom: '1rem' }}
            />
          </label>
          <button type="submit" disabled={status === 'loading'}>
            {status === 'loading' ? 'Looking up…' : 'Find order'}
          </button>
        </form>
      )}

      {step === 'select' && lookup && (
        <form onSubmit={handleSubmit}>
          <p>
            Order <strong>{lookup.order_number}</strong>
          </p>
          <label>
            Item to return
            <select
              required
              value={selectedItemId}
              onChange={(event) => setSelectedItemId(event.target.value)}
              style={{ display: 'block', width: '100%', marginBottom: '1rem' }}
            >
              {lookup.items.map((item) => (
                <option key={item.id} value={item.id}>
                  {item.product_name} (qty {item.quantity})
                </option>
              ))}
            </select>
          </label>
          <label>
            Quantity
            <input
              required
              type="number"
              min={1}
              max={lookup.items.find((item) => item.id === selectedItemId)?.quantity ?? 1}
              value={quantity}
              onChange={(event) => setQuantity(Number(event.target.value))}
              style={{ display: 'block', width: '100%', marginBottom: '1rem' }}
            />
          </label>
          <label>
            Reason
            <input
              required
              value={reason}
              onChange={(event) => setReason(event.target.value)}
              style={{ display: 'block', width: '100%', marginBottom: '1rem' }}
            />
          </label>
          <button type="submit" disabled={status === 'loading'}>
            {status === 'loading' ? 'Submitting…' : 'Submit return request'}
          </button>
        </form>
      )}

      {step === 'done' && confirmationId && (
        <p>
          Your return request was submitted. Reference: <strong>{confirmationId}</strong>. The
          merchant will review it shortly.
        </p>
      )}
    </main>
  );
}
