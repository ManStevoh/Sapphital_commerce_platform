'use client';

import { useState } from 'react';
import { addToCart } from '@/lib/api';
import { getSessionId } from '@/lib/session';

interface AddToCartButtonProps {
  productId: string;
  tenantId: string;
  disabled?: boolean;
}

export function AddToCartButton({
  productId,
  tenantId,
  disabled = false,
}: AddToCartButtonProps) {
  const [status, setStatus] = useState<'idle' | 'loading' | 'success' | 'error'>('idle');
  const [message, setMessage] = useState<string | null>(null);

  async function handleClick() {
    setStatus('loading');
    setMessage(null);

    try {
      const sessionId = getSessionId();
      const result = await addToCart(productId, 1, sessionId, tenantId);
      setStatus('success');
      setMessage(
        `Added to cart — total ${(result.data.cart.total_kobo / 100).toLocaleString('en-NG', { style: 'currency', currency: 'NGN' })}`,
      );
    } catch (err) {
      setStatus('error');
      setMessage(err instanceof Error ? err.message : 'Failed to add to cart.');
    }
  }

  return (
    <div>
      <button type="button" onClick={handleClick} disabled={disabled || status === 'loading'}>
        {status === 'loading' ? 'Adding…' : 'Add to Cart'}
      </button>
      {message && (
        <p style={{ color: status === 'error' ? 'crimson' : 'green', marginTop: '0.5rem' }}>
          {message}
        </p>
      )}
    </div>
  );
}
