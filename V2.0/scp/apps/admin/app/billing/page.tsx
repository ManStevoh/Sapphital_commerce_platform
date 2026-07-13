'use client';

import { Suspense, useEffect, useState } from 'react';
import { useRouter, useSearchParams } from 'next/navigation';
import { AdminShell, Alert, Button, Card, Table, Td, Th } from '@sapphital/scp-ui';
import {
  activateBillingSubscription,
  clearAuth,
  fetchBillingSettings,
  fetchBillingSubscription,
  formatNgn,
  getStoredTenantId,
  getStoredToken,
  initializeBillingPayment,
  listBillingInvoices,
  downloadBillingInvoicePdf,
  updateBillingSettings,
  type BillingInvoice,
  type BillingSettings,
  type BillingSubscription,
} from '@/lib/api';
import { adminNav } from '@/lib/nav';

function BillingContent() {
  const router = useRouter();
  const searchParams = useSearchParams();
  const showWelcome = searchParams.get('welcome') === '1';
  const prefilledEmail = searchParams.get('email') ?? '';
  const [subscription, setSubscription] = useState<BillingSubscription | null>(null);
  const [billingSettings, setBillingSettings] = useState<BillingSettings | null>(null);
  const [invoices, setInvoices] = useState<BillingInvoice[]>([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const [success, setSuccess] = useState<string | null>(null);
  const [working, setWorking] = useState(false);
  const [billingEmail, setBillingEmail] = useState(prefilledEmail);

  useEffect(() => {
    if (prefilledEmail) {
      setBillingEmail(prefilledEmail);
    }
  }, [prefilledEmail]);

  useEffect(() => {
    const token = getStoredToken();
    const tenantId = getStoredTenantId();

    if (!token || !tenantId) {
      router.replace('/login');
      return;
    }

    Promise.all([
      fetchBillingSubscription(tenantId),
      listBillingInvoices(tenantId),
      fetchBillingSettings(tenantId),
    ])
      .then(([sub, invoiceList, settings]) => {
        setSubscription(sub);
        setInvoices(invoiceList);
        setBillingSettings(settings);
      })
      .catch((err) => {
        setError(err instanceof Error ? err.message : 'Failed to load billing.');
      })
      .finally(() => {
        setLoading(false);
      });
  }, [router]);

  async function handlePayWithPaystack() {
    const tenantId = getStoredTenantId();

    if (!tenantId || !billingEmail.trim()) {
      setError('Billing email is required.');
      return;
    }

    setWorking(true);
    setError(null);
    setSuccess(null);

    try {
      const payment = await initializeBillingPayment(tenantId, billingEmail.trim());
      window.location.href = payment.authorization_url;
    } catch (err) {
      setError(err instanceof Error ? err.message : 'Failed to start payment.');
      setWorking(false);
    }
  }

  async function handleManualActivate() {
    const tenantId = getStoredTenantId();

    if (!tenantId) {
      return;
    }

    setWorking(true);
    setError(null);
    setSuccess(null);

    try {
      const result = await activateBillingSubscription(tenantId);
      setSubscription(result.subscription);
      setSuccess('Subscription activated.');
      const invoiceList = await listBillingInvoices(tenantId);
      setInvoices(invoiceList);
    } catch (err) {
      setError(err instanceof Error ? err.message : 'Activation failed.');
    } finally {
      setWorking(false);
    }
  }

  function handleLogout() {
    clearAuth();
    router.push('/login');
  }

  const canPay =
    subscription?.status === 'trial' ||
    subscription?.status === 'past_due' ||
    subscription?.status === 'suspended';

  if (loading) {
    return (
      <AdminShell title="Billing" subtitle="Loading…" nav={adminNav} activeHref="/billing">
        <p>Loading billing…</p>
      </AdminShell>
    );
  }

  return (
    <AdminShell
      title="Billing"
      subtitle={subscription?.plan?.name ?? 'Subscription'}
      nav={adminNav}
      activeHref="/billing"
      onSignOut={handleLogout}
    >
      {showWelcome && subscription?.status === 'trial' && (
        <Alert variant="success">
          Welcome! Your 14-day trial has started. Add a payment method below to stay active after
          the trial ends.
        </Alert>
      )}

      {error && <Alert>{error}</Alert>}
      {success && <Alert variant="success">{success}</Alert>}

      {subscription && (
        <Card>
          <h2>Current plan</h2>
          <p>
            <strong>{subscription.plan?.name ?? '—'}</strong> —{' '}
            {subscription.plan ? formatNgn(subscription.plan.price_ngn) : '—'} / month
          </p>
          <p>Status: {subscription.status}</p>
          {subscription.trial_ends_at && (
            <p>Trial ends: {new Date(subscription.trial_ends_at).toLocaleString()}</p>
          )}
          {subscription.current_period_end && (
            <p>Current period ends: {new Date(subscription.current_period_end).toLocaleString()}</p>
          )}

          {canPay && (
            <div style={{ marginTop: '1rem', display: 'flex', flexDirection: 'column', gap: '0.5rem' }}>
              <input
                type="email"
                placeholder="Billing email"
                value={billingEmail}
                onChange={(event) => setBillingEmail(event.target.value)}
              />
              <Button type="button" disabled={working} onClick={handlePayWithPaystack}>
                Pay with Paystack
              </Button>
              <Button type="button" variant="secondary" disabled={working} onClick={handleManualActivate}>
                Activate (manual / stub)
              </Button>
            </div>
          )}
        </Card>
      )}

      {billingSettings && (
        <Card style={{ marginTop: '1.5rem' }}>
          <h2>Tax settings</h2>
          <p>
            Enable when your business is VAT-registered in Nigeria. Platform invoices will include
            7.5% VAT on subscription fees.
          </p>
          <label style={{ display: 'flex', alignItems: 'center', gap: '0.5rem' }}>
            <input
              type="checkbox"
              checked={billingSettings.vat_registered}
              disabled={working}
              onChange={async (event) => {
                const tenantId = getStoredTenantId();
                if (!tenantId) {
                  return;
                }
                setWorking(true);
                setError(null);
                try {
                  const updated = await updateBillingSettings(tenantId, {
                    vat_registered: event.target.checked,
                  });
                  setBillingSettings(updated);
                  setSuccess('Tax settings updated.');
                } catch (err) {
                  setError(err instanceof Error ? err.message : 'Failed to update tax settings.');
                } finally {
                  setWorking(false);
                }
              }}
            />
            VAT registered (Nigeria)
          </label>
        </Card>
      )}

      <h2 style={{ marginTop: '1.5rem' }}>Invoices</h2>
      {invoices.length === 0 ? (
        <p>No invoices yet.</p>
      ) : (
        <Table aria-label="Invoice list">
          <thead>
            <tr>
              <Th>Number</Th>
              <Th>Status</Th>
              <Th>Total</Th>
              <Th>Period</Th>
              <Th>Created</Th>
              <Th>PDF</Th>
            </tr>
          </thead>
          <tbody>
            {invoices.map((invoice) => (
              <tr key={invoice.id}>
                <Td>{invoice.number}</Td>
                <Td>{invoice.status}</Td>
                <Td>{formatNgn(invoice.total)}</Td>
                <Td>
                  {invoice.period_start && invoice.period_end
                    ? `${invoice.period_start} → ${invoice.period_end}`
                    : '—'}
                </Td>
                <Td>
                  {invoice.created_at
                    ? new Date(invoice.created_at).toLocaleString()
                    : '—'}
                </Td>
                <Td>
                  <Button
                    type="button"
                    variant="secondary"
                    disabled={working}
                    onClick={async () => {
                      const tenantId = getStoredTenantId();
                      if (!tenantId) {
                        return;
                      }
                      setWorking(true);
                      try {
                        await downloadBillingInvoicePdf(tenantId, invoice.id);
                      } catch (err) {
                        setError(err instanceof Error ? err.message : 'PDF download failed.');
                      } finally {
                        setWorking(false);
                      }
                    }}
                  >
                    Download
                  </Button>
                </Td>
              </tr>
            ))}
          </tbody>
        </Table>
      )}
    </AdminShell>
  );
}

export default function BillingPage() {
  return (
    <Suspense fallback={
      <AdminShell title="Billing" subtitle="Loading…" nav={adminNav} activeHref="/billing">
        <p>Loading billing…</p>
      </AdminShell>
    }>
      <BillingContent />
    </Suspense>
  );
}
