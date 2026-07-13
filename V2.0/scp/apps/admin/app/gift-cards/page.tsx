'use client';

import { FormEvent, useEffect, useState } from 'react';
import { useRouter } from 'next/navigation';
import { AdminShell, Alert, Button, Card, Input } from '@sapphital/scp-ui';
import {
  clearAuth,
  disableGiftCard,
  getStoredTenantId,
  getStoredToken,
  issueGiftCard,
  lookupGiftCard,
  type GiftCard,
} from '@/lib/api';
import { adminNav } from '@/lib/nav';

const PRESETS = [
  { label: '₦5,000', value: 500_000 },
  { label: '₦10,000', value: 1_000_000 },
  { label: '₦25,000', value: 2_500_000 },
] as const;

function formatNgn(kobo: number): string {
  return new Intl.NumberFormat('en-NG', {
    style: 'currency',
    currency: 'NGN',
    maximumFractionDigits: 0,
  }).format(kobo / 100);
}

export default function GiftCardsAdminPage() {
  const router = useRouter();
  const [ready, setReady] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [working, setWorking] = useState(false);
  const [denomination, setDenomination] = useState<number>(500_000);
  const [recipientEmail, setRecipientEmail] = useState('');
  const [lookupCode, setLookupCode] = useState('');
  const [card, setCard] = useState<GiftCard | null>(null);

  useEffect(() => {
    const token = getStoredToken();
    const tenantId = getStoredTenantId();

    if (!token || !tenantId) {
      router.replace('/login');
      return;
    }

    setReady(true);
  }, [router]);

  async function handleIssue(event: FormEvent<HTMLFormElement>) {
    event.preventDefault();
    const tenantId = getStoredTenantId();

    if (!tenantId) {
      return;
    }

    setWorking(true);
    setError(null);

    try {
      const issued = await issueGiftCard(tenantId, {
        denomination_kobo: denomination,
        recipient_email: recipientEmail.trim() || undefined,
      });
      setCard(issued);
      setRecipientEmail('');
    } catch (err) {
      setError(err instanceof Error ? err.message : 'Failed to issue gift card.');
    } finally {
      setWorking(false);
    }
  }

  async function handleLookup(event: FormEvent<HTMLFormElement>) {
    event.preventDefault();
    const tenantId = getStoredTenantId();

    if (!tenantId || !lookupCode.trim()) {
      return;
    }

    setWorking(true);
    setError(null);

    try {
      const found = await lookupGiftCard(tenantId, lookupCode.trim());
      setCard(found);
    } catch (err) {
      setError(err instanceof Error ? err.message : 'Gift card not found.');
      setCard(null);
    } finally {
      setWorking(false);
    }
  }

  async function handleDisable() {
    const tenantId = getStoredTenantId();

    if (!tenantId || !card) {
      return;
    }

    setWorking(true);
    setError(null);

    try {
      const updated = await disableGiftCard(tenantId, card.id);
      setCard(updated);
    } catch (err) {
      setError(err instanceof Error ? err.message : 'Failed to disable gift card.');
    } finally {
      setWorking(false);
    }
  }

  if (!ready) {
    return (
      <AdminShell title="Gift cards" subtitle="Loading…" nav={adminNav} activeHref="/gift-cards">
        <p>Loading…</p>
      </AdminShell>
    );
  }

  return (
    <AdminShell
      title="Gift cards"
      subtitle="Issue · look up · disable"
      nav={adminNav}
      activeHref="/gift-cards"
      onLogout={() => {
        clearAuth();
        router.push('/login');
      }}
    >
      {error && <Alert variant="error">{error}</Alert>}

      <Card title="Issue gift card">
        <form onSubmit={handleIssue}>
          <label style={{ display: 'block', marginBottom: 12 }}>
            <span style={{ display: 'block', marginBottom: 4, fontSize: '0.875rem' }}>
              Denomination
            </span>
            <select
              value={denomination}
              onChange={(e) => setDenomination(Number(e.target.value))}
              style={{
                width: '100%',
                padding: '8px 12px',
                borderRadius: 4,
                border: '1px solid var(--color-border)',
              }}
            >
              {PRESETS.map((preset) => (
                <option key={preset.value} value={preset.value}>
                  {preset.label}
                </option>
              ))}
            </select>
          </label>
          <Input
            label="Recipient email (optional)"
            type="email"
            value={recipientEmail}
            onChange={(e) => setRecipientEmail(e.target.value)}
          />
          <Button type="submit" disabled={working}>
            {working ? 'Issuing…' : 'Issue code'}
          </Button>
        </form>
      </Card>

      <Card title="Look up by code">
        <form onSubmit={handleLookup}>
          <Input
            label="Code"
            value={lookupCode}
            onChange={(e) => setLookupCode(e.target.value)}
            placeholder="GC-XXXX-XXXX"
          />
          <Button type="submit" variant="secondary" disabled={working}>
            Look up
          </Button>
        </form>
      </Card>

      {card && (
        <Card title={`Card ${card.code}`}>
          <p style={{ marginTop: 0 }}>
            Balance: {formatNgn(card.balance_kobo)} of {formatNgn(card.initial_balance_kobo)} ·{' '}
            Status: {card.status}
          </p>
          {card.recipient_email && <p>Recipient: {card.recipient_email}</p>}
          {card.expires_at && <p>Expires: {new Date(card.expires_at).toLocaleString()}</p>}
          {card.status === 'active' && (
            <Button type="button" variant="secondary" disabled={working} onClick={handleDisable}>
              Disable card
            </Button>
          )}
        </Card>
      )}
    </AdminShell>
  );
}
