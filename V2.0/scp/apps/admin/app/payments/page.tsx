'use client';

import { useEffect, useState } from 'react';
import { useRouter } from 'next/navigation';
import { AdminShell, Alert, Button, Card, Table, Td, Th } from '@sapphital/scp-ui';
import {
  clearAuth,
  downloadPaymentReconciliationCsv,
  fetchPaymentCredentials,
  fetchPaymentProviderSettings,
  fetchPaymentReconciliation,
  formatNgn,
  getStoredTenantId,
  getStoredToken,
  updatePaymentCredentials,
  updatePaymentProvider,
  type PaymentCredentialsStatus,
  type PaymentProviderSettings,
  type PaymentReconciliationReport,
} from '@/lib/api';
import { adminNav } from '@/lib/nav';

function defaultFromDate(): string {
  const date = new Date();
  date.setDate(date.getDate() - 30);
  return date.toISOString().slice(0, 10);
}

function defaultToDate(): string {
  return new Date().toISOString().slice(0, 10);
}

export default function PaymentsPage() {
  const router = useRouter();
  const [fromDate, setFromDate] = useState(defaultFromDate);
  const [toDate, setToDate] = useState(defaultToDate);
  const [report, setReport] = useState<PaymentReconciliationReport | null>(null);
  const [storeSettings, setStoreSettings] = useState<PaymentProviderSettings | null>(null);
  const [paymentProvider, setPaymentProvider] = useState<'paystack' | 'flutterwave'>('paystack');
  const [credentials, setCredentials] = useState<PaymentCredentialsStatus | null>(null);
  const [paystackSecretKey, setPaystackSecretKey] = useState('');
  const [flutterwaveSecretKey, setFlutterwaveSecretKey] = useState('');
  const [flutterwaveSecretHash, setFlutterwaveSecretHash] = useState('');
  const [loading, setLoading] = useState(true);
  const [working, setWorking] = useState(false);
  const [error, setError] = useState<string | null>(null);

  async function loadReport(from: string, to: string) {
    const tenantId = getStoredTenantId();

    if (!tenantId) {
      return;
    }

    setWorking(true);
    setError(null);

    try {
      const data = await fetchPaymentReconciliation(tenantId, from, to);
      setReport(data);
    } catch (err) {
      setError(err instanceof Error ? err.message : 'Failed to load reconciliation report.');
    } finally {
      setWorking(false);
    }
  }

  useEffect(() => {
    const token = getStoredToken();
    const tenantId = getStoredTenantId();

    if (!token || !tenantId) {
      router.replace('/login');
      return;
    }

    Promise.all([
      loadReport(fromDate, toDate),
      fetchPaymentProviderSettings(tenantId).then((settings) => {
        setStoreSettings(settings);
        setPaymentProvider(settings.payment_provider);
      }),
      fetchPaymentCredentials(tenantId).then(setCredentials),
    ]).finally(() => {
      setLoading(false);
    });
  }, [router]);

  async function handleApplyFilter(event: React.FormEvent) {
    event.preventDefault();
    await loadReport(fromDate, toDate);
  }

  async function handleExport() {
    const tenantId = getStoredTenantId();

    if (!tenantId) {
      return;
    }

    setWorking(true);
    setError(null);

    try {
      await downloadPaymentReconciliationCsv(tenantId, fromDate, toDate);
    } catch (err) {
      setError(err instanceof Error ? err.message : 'Failed to export CSV.');
    } finally {
      setWorking(false);
    }
  }

  async function handleSavePaymentProvider() {
    const tenantId = getStoredTenantId();

    if (!tenantId) {
      return;
    }

    setWorking(true);
    setError(null);

    try {
      const settings = await updatePaymentProvider(tenantId, paymentProvider);
      setStoreSettings({
        payment_provider: settings.payment_provider,
        currency: settings.currency,
      });
    } catch (err) {
      setError(err instanceof Error ? err.message : 'Failed to update payment provider.');
    } finally {
      setWorking(false);
    }
  }

  async function handleSaveCredentials(provider: 'paystack' | 'flutterwave') {
    const tenantId = getStoredTenantId();

    if (!tenantId) {
      return;
    }

    setWorking(true);
    setError(null);

    try {
      const payload =
        provider === 'paystack'
          ? { provider, secret_key: paystackSecretKey }
          : {
              provider,
              secret_key: flutterwaveSecretKey,
              secret_hash: flutterwaveSecretHash,
            };

      const status = await updatePaymentCredentials(tenantId, payload);
      setCredentials(status);

      if (provider === 'paystack') {
        setPaystackSecretKey('');
      } else {
        setFlutterwaveSecretKey('');
        setFlutterwaveSecretHash('');
      }
    } catch (err) {
      setError(err instanceof Error ? err.message : 'Failed to save payment credentials.');
    } finally {
      setWorking(false);
    }
  }

  function handleLogout() {
    clearAuth();
    router.push('/login');
  }

  if (loading) {
    return (
      <AdminShell title="Payments" subtitle="Loading…" nav={adminNav} activeHref="/payments">
        <p>Loading reconciliation report…</p>
      </AdminShell>
    );
  }

  return (
    <AdminShell
      title="Payments"
      subtitle="Reconciliation"
      nav={adminNav}
      activeHref="/payments"
      onSignOut={handleLogout}
    >
      {error && <Alert>{error}</Alert>}

      <Card>
        <h2 style={{ marginTop: 0 }}>Checkout payment provider</h2>
        <p style={{ color: 'var(--color-text-secondary)' }}>
          Customers are redirected to this PSP at checkout. Leave credentials blank to use platform
          keys (dev/stub) or add your own PSP secret keys below.
        </p>
        <div style={{ display: 'flex', gap: '1rem', alignItems: 'end', flexWrap: 'wrap' }}>
          <label style={{ display: 'flex', flexDirection: 'column', gap: '0.25rem' }}>
            Provider
            <select
              value={paymentProvider}
              onChange={(event) =>
                setPaymentProvider(event.target.value as 'paystack' | 'flutterwave')
              }
            >
              <option value="paystack">Paystack</option>
              <option value="flutterwave">Flutterwave</option>
            </select>
          </label>
          <Button
            type="button"
            disabled={working || storeSettings?.payment_provider === paymentProvider}
            onClick={handleSavePaymentProvider}
          >
            Save provider
          </Button>
        </div>
      </Card>

      <Card style={{ marginTop: '1.5rem' }}>
        <h2 style={{ marginTop: 0 }}>PSP credentials</h2>
        <p style={{ color: 'var(--color-text-secondary)' }}>
          Merchant-owned keys are stored in the secrets vault (never returned in full). Webhooks
          verify using the matching tenant key when a payment reference is known.
        </p>

        <div style={{ display: 'grid', gap: '1.5rem', marginTop: '1rem' }}>
          <div>
            <h3 style={{ marginTop: 0 }}>Paystack</h3>
            {credentials?.paystack.configured ? (
              <p>Configured: {credentials.paystack.masked_secret_key}</p>
            ) : credentials?.paystack.uses_platform_key ? (
              <p>Using platform key</p>
            ) : (
              <p>Not configured — stub mode in dev</p>
            )}
            <label style={{ display: 'flex', flexDirection: 'column', gap: '0.25rem', maxWidth: 420 }}>
              Secret key
              <input
                type="password"
                value={paystackSecretKey}
                onChange={(event) => setPaystackSecretKey(event.target.value)}
                placeholder="sk_live_…"
                autoComplete="off"
              />
            </label>
            <Button
              type="button"
              style={{ marginTop: '0.75rem' }}
              disabled={working || paystackSecretKey === ''}
              onClick={() => handleSaveCredentials('paystack')}
            >
              Save Paystack key
            </Button>
          </div>

          <div>
            <h3 style={{ marginTop: 0 }}>Flutterwave</h3>
            {credentials?.flutterwave.configured ? (
              <p>Configured: {credentials.flutterwave.masked_secret_key}</p>
            ) : credentials?.flutterwave.uses_platform_key ? (
              <p>Using platform key</p>
            ) : (
              <p>Not configured — stub mode in dev</p>
            )}
            {credentials?.flutterwave.webhook_hash_configured && (
              <p>Webhook hash configured</p>
            )}
            <div style={{ display: 'flex', flexWrap: 'wrap', gap: '1rem' }}>
              <label style={{ display: 'flex', flexDirection: 'column', gap: '0.25rem', minWidth: 200 }}>
                Secret key
                <input
                  type="password"
                  value={flutterwaveSecretKey}
                  onChange={(event) => setFlutterwaveSecretKey(event.target.value)}
                  placeholder="FLWSECK_…"
                  autoComplete="off"
                />
              </label>
              <label style={{ display: 'flex', flexDirection: 'column', gap: '0.25rem', minWidth: 200 }}>
                Webhook hash
                <input
                  type="password"
                  value={flutterwaveSecretHash}
                  onChange={(event) => setFlutterwaveSecretHash(event.target.value)}
                  placeholder="verif-hash secret"
                  autoComplete="off"
                />
              </label>
            </div>
            <Button
              type="button"
              style={{ marginTop: '0.75rem' }}
              disabled={
                working || (flutterwaveSecretKey === '' && flutterwaveSecretHash === '')
              }
              onClick={() => handleSaveCredentials('flutterwave')}
            >
              Save Flutterwave keys
            </Button>
          </div>
        </div>
      </Card>

      <Card style={{ marginTop: '1.5rem' }}>
        <form
          onSubmit={handleApplyFilter}
          style={{ display: 'flex', flexWrap: 'wrap', gap: '0.75rem', alignItems: 'end' }}
        >
          <label style={{ display: 'flex', flexDirection: 'column', gap: '0.25rem' }}>
            From
            <input
              type="date"
              value={fromDate}
              onChange={(event) => setFromDate(event.target.value)}
            />
          </label>
          <label style={{ display: 'flex', flexDirection: 'column', gap: '0.25rem' }}>
            To
            <input
              type="date"
              value={toDate}
              onChange={(event) => setToDate(event.target.value)}
            />
          </label>
          <Button type="submit" disabled={working}>
            Apply
          </Button>
          <Button type="button" variant="secondary" disabled={working} onClick={handleExport}>
            Export CSV
          </Button>
        </form>
      </Card>

      {report && (
        <>
          <div
            style={{
              display: 'grid',
              gridTemplateColumns: 'repeat(auto-fit, minmax(180px, 1fr))',
              gap: '1rem',
              marginTop: '1.5rem',
            }}
          >
            <Card>
              <p>Charges</p>
              <strong>{report.summary.charge_count}</strong>
              <p>{formatNgn(report.summary.total_charges_kobo)}</p>
            </Card>
            <Card>
              <p>Refunds</p>
              <strong>{report.summary.refund_count}</strong>
              <p>{formatNgn(report.summary.total_refunds_kobo)}</p>
            </Card>
            <Card>
              <p>Net</p>
              <strong>{formatNgn(report.summary.net_kobo)}</strong>
              <p>
                {report.period.from} → {report.period.to}
              </p>
            </Card>
          </div>

          <h2 style={{ marginTop: '1.5rem' }}>Ledger</h2>
          {report.entries.length === 0 ? (
            <p>No payment activity in this period.</p>
          ) : (
            <Table aria-label="Payment reconciliation ledger">
              <thead>
                <tr>
                  <Th>Type</Th>
                  <Th>Date</Th>
                  <Th>Order</Th>
                  <Th>Reference</Th>
                  <Th>Amount</Th>
                  <Th>Status</Th>
                </tr>
              </thead>
              <tbody>
                {report.entries.map((entry) => (
                  <tr key={`${entry.type}-${entry.reference}-${entry.occurred_at}`}>
                    <Td>{entry.type}</Td>
                    <Td>{new Date(entry.occurred_at).toLocaleString()}</Td>
                    <Td>{entry.order_number ?? entry.order_id}</Td>
                    <Td>{entry.reference}</Td>
                    <Td>
                      {entry.type === 'refund' ? '−' : ''}
                      {formatNgn(entry.amount_kobo)}
                    </Td>
                    <Td>{entry.status}</Td>
                  </tr>
                ))}
              </tbody>
            </Table>
          )}
        </>
      )}
    </AdminShell>
  );
}
