'use client';

import Link from 'next/link';
import { FormEvent, useEffect, useMemo, useState } from 'react';
import { Alert, Button, Card, Input } from '@sapphital/scp-ui';
import { CartSummary } from '@/components/CartSummary';
import {
  createCheckout,
  formatNgn,
  getCart,
  getShippingRates,
  initializePayment,
  updateCheckoutSession,
  type Cart,
  type CheckoutSession,
  type PaymentInitialization,
  type ShippingRate,
} from '@/lib/api';
import { isValidNgPhone, NIGERIA_STATES, normalizeNgPhone } from '@/lib/nigeria-states';
import { getSessionId } from '@/lib/session';
import { resolveClientTenantId } from '@/lib/tenant-client';

type Step = 'loading' | 'contact' | 'shipping' | 'review' | 'paying' | 'redirect' | 'error';

export default function CheckoutPage() {
  const [step, setStep] = useState<Step>('loading');
  const [cart, setCart] = useState<Cart | null>(null);
  const [checkoutSession, setCheckoutSession] = useState<CheckoutSession | null>(null);
  const [shippingRates, setShippingRates] = useState<ShippingRate[]>([]);
  const [selectedShippingRateId, setSelectedShippingRateId] = useState<string | null>(null);
  const [email, setEmail] = useState('');
  const [phone, setPhone] = useState('');
  const [line1, setLine1] = useState('');
  const [city, setCity] = useState('');
  const [state, setState] = useState('Lagos');
  const [lga, setLga] = useState('');
  const [tenantId, setTenantId] = useState<string | null>(null);
  const [payment, setPayment] = useState<PaymentInitialization | null>(null);
  const [error, setError] = useState<string | null>(null);

  useEffect(() => {
    let cancelled = false;

    async function loadCheckout() {
      try {
        const sessionId = getSessionId();
        const resolvedTenantId = await resolveClientTenantId();
        const cartData = await getCart(sessionId, resolvedTenantId);

        if (cancelled) {
          return;
        }

        if (cartData.items.length === 0) {
          setError('Your cart is empty. Add items before checkout.');
          setStep('error');
          return;
        }

        setTenantId(resolvedTenantId);
        setCart(cartData);

        const rates = await getShippingRates(cartData.total_kobo, resolvedTenantId);

        if (cancelled) {
          return;
        }

        setShippingRates(rates);
        setSelectedShippingRateId(rates[0]?.id ?? null);
        setStep('contact');
      } catch (err) {
        if (!cancelled) {
          setError(err instanceof Error ? err.message : 'Failed to load checkout.');
          setStep('error');
        }
      }
    }

    loadCheckout();

    return () => {
      cancelled = true;
    };
  }, []);

  const checkoutTotalKobo = useMemo(() => {
    if (checkoutSession?.total_kobo) {
      return checkoutSession.total_kobo;
    }

    const shipping = shippingRates.find((rate) => rate.id === selectedShippingRateId);
    return (cart?.total_kobo ?? 0) + (shipping?.price_kobo ?? 0);
  }, [cart, checkoutSession, selectedShippingRateId, shippingRates]);

  async function handleContactSubmit(event: FormEvent<HTMLFormElement>) {
    event.preventDefault();
    setError(null);

    if (!isValidNgPhone(phone)) {
      setError('Enter a valid Nigeria phone number (+234 or 0…).');
      return;
    }

    setStep('shipping');
  }

  async function handleShippingSubmit(event: FormEvent<HTMLFormElement>) {
    event.preventDefault();

    if (!cart || !tenantId || !selectedShippingRateId) {
      setError('Select a shipping method.');
      return;
    }

    setError(null);
    setStep('review');

    try {
      let session = checkoutSession;

      if (!session) {
        session = await createCheckout(cart.id, tenantId);
        setCheckoutSession(session);
      }

      const updated = await updateCheckoutSession(session.session_id, tenantId, {
        customer_email: email.trim(),
        customer_phone: normalizeNgPhone(phone),
        shipping_rate_id: selectedShippingRateId,
        shipping_address: {
          line1: line1.trim(),
          city: city.trim(),
          state,
          lga: lga.trim() || undefined,
        },
      });

      setCheckoutSession(updated);
    } catch (err) {
      setError(err instanceof Error ? err.message : 'Failed to save shipping details.');
      setStep('shipping');
    }
  }

  async function handlePay(event: FormEvent<HTMLFormElement>) {
    event.preventDefault();

    if (!checkoutSession || !tenantId) {
      return;
    }

    setStep('paying');
    setError(null);

    try {
      const paymentResult = await initializePayment(
        checkoutSession.session_id,
        email.trim(),
        tenantId,
      );

      setPayment(paymentResult);
      setStep('redirect');
      window.location.href = paymentResult.authorization_url;
    } catch (err) {
      setError(err instanceof Error ? err.message : 'Payment initialization failed.');
      setStep('review');
    }
  }

  return (
    <main style={{ maxWidth: 960, margin: '0 auto', padding: '2rem 1rem' }}>
      <header style={{ marginBottom: '2rem' }}>
        <p>
          <Link href="/cart">&larr; Back to cart</Link>
        </p>
        <h1 style={{ margin: 0 }}>Checkout</h1>
        <p style={{ color: 'var(--color-text-secondary)' }}>
          Contact → Shipping → Review → Pay with Paystack
        </p>
      </header>

      {step === 'loading' && <p>Preparing checkout…</p>}

      {step === 'error' && (
        <div>
          <Alert>{error}</Alert>
          <Link href="/">Continue shopping</Link>
        </div>
      )}

      {cart && step !== 'loading' && step !== 'error' && (
        <div
          style={{
            display: 'grid',
            gridTemplateColumns: 'minmax(0, 1fr) minmax(260px, 320px)',
            gap: '2rem',
            alignItems: 'start',
          }}
        >
          <section>
            {step === 'contact' && (
              <Card title="1. Contact">
                <form onSubmit={handleContactSubmit}>
                  <Input
                    label="Email"
                    type="email"
                    required
                    value={email}
                    onChange={(e) => setEmail(e.target.value)}
                    autoComplete="email"
                  />
                  <Input
                    label="Phone (+234)"
                    type="tel"
                    required
                    value={phone}
                    onChange={(e) => setPhone(e.target.value)}
                    placeholder="08012345678"
                  />
                  {error && <Alert>{error}</Alert>}
                  <Button type="submit">Continue to shipping</Button>
                </form>
              </Card>
            )}

            {step === 'shipping' && (
              <Card title="2. Shipping">
                <form onSubmit={handleShippingSubmit}>
                  <Input
                    label="Address line"
                    required
                    value={line1}
                    onChange={(e) => setLine1(e.target.value)}
                  />
                  <Input
                    label="City"
                    required
                    value={city}
                    onChange={(e) => setCity(e.target.value)}
                  />
                  <label style={{ display: 'block', marginBottom: 16 }}>
                    <span style={{ display: 'block', marginBottom: 4, fontSize: '0.875rem' }}>
                      State
                    </span>
                    <select
                      value={state}
                      onChange={(e) => setState(e.target.value)}
                      style={{
                        width: '100%',
                        padding: '8px 12px',
                        borderRadius: 4,
                        border: '1px solid var(--color-border)',
                      }}
                    >
                      {NIGERIA_STATES.map((item) => (
                        <option key={item} value={item}>
                          {item}
                        </option>
                      ))}
                    </select>
                  </label>
                  <Input label="LGA (optional)" value={lga} onChange={(e) => setLga(e.target.value)} />
                  {error && <Alert>{error}</Alert>}
                  <div style={{ display: 'flex', gap: 8 }}>
                    <Button type="button" variant="secondary" onClick={() => setStep('contact')}>
                      Back
                    </Button>
                    <Button type="submit">Review order</Button>
                  </div>
                </form>
              </Card>
            )}

            {(step === 'review' || step === 'paying' || step === 'redirect') && (
              <Card title="3. Review & pay">
                <p>
                  <strong>{email}</strong> · {normalizeNgPhone(phone)}
                </p>
                <p>
                  {line1}, {city}, {state}
                  {lga ? ` (${lga})` : ''}
                </p>
                <form onSubmit={handlePay}>
                  {error && <Alert>{error}</Alert>}
                  <Button type="submit" disabled={step === 'paying' || step === 'redirect'}>
                    {step === 'paying' || step === 'redirect'
                      ? 'Redirecting to Paystack…'
                      : `Pay ${formatNgn(checkoutTotalKobo)} with Paystack`}
                  </Button>
                </form>
                {payment && step === 'redirect' && (
                  <p style={{ marginTop: 12 }}>
                    If you are not redirected,{' '}
                    <a href={payment.authorization_url}>continue to Paystack</a>.
                  </p>
                )}
              </Card>
            )}
          </section>

          <CartSummary
            cart={cart}
            shippingRates={shippingRates}
            selectedShippingRateId={selectedShippingRateId}
            onSelectShippingRate={setSelectedShippingRateId}
            showCheckoutLink={false}
            totalOverrideKobo={checkoutTotalKobo}
          />
        </div>
      )}
    </main>
  );
}
