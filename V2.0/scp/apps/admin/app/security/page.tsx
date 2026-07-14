'use client';

import { FormEvent, useEffect, useState } from 'react';
import { useRouter } from 'next/navigation';
import { AdminShell, Alert, Button, Card, Input } from '@sapphital/scp-ui';
import {
  clearAuth,
  createMerchantSessionToken,
  fetchMerchantSessions,
  getStoredTenantId,
  getStoredToken,
  revokeMerchantSession,
  type MerchantSession,
} from '@/lib/api';
import { adminNav } from '@/lib/nav';

export default function SecurityPage() {
  const router = useRouter();
  const [sessions, setSessions] = useState<MerchantSession[]>([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const [working, setWorking] = useState(false);
  const [tokenName, setTokenName] = useState('api-integration');
  const [newPlainToken, setNewPlainToken] = useState<string | null>(null);

  async function loadSessions(token: string) {
    const data = await fetchMerchantSessions(token);
    setSessions(data);
  }

  useEffect(() => {
    const token = getStoredToken();
    const tenantId = getStoredTenantId();

    if (!token || !tenantId) {
      router.replace('/login');
      return;
    }

    loadSessions(token)
      .catch((err) => {
        setError(err instanceof Error ? err.message : 'Failed to load sessions.');
      })
      .finally(() => setLoading(false));
  }, [router]);

  async function handleCreateToken(event: FormEvent<HTMLFormElement>) {
    event.preventDefault();
    const token = getStoredToken();

    if (!token || !tokenName.trim()) {
      return;
    }

    setWorking(true);
    setError(null);
    setNewPlainToken(null);

    try {
      const created = await createMerchantSessionToken(token, tokenName.trim());
      setNewPlainToken(created.token);
      await loadSessions(token);
      setTokenName('api-integration');
    } catch (err) {
      setError(err instanceof Error ? err.message : 'Failed to create API token.');
    } finally {
      setWorking(false);
    }
  }

  async function handleRevoke(sessionId: string, isCurrent: boolean) {
    const token = getStoredToken();

    if (!token) {
      return;
    }

    if (isCurrent && !window.confirm('Revoke this session? You will be signed out.')) {
      return;
    }

    setWorking(true);
    setError(null);

    try {
      await revokeMerchantSession(token, sessionId);

      if (isCurrent) {
        clearAuth();
        router.push('/login');
        return;
      }

      await loadSessions(token);
    } catch (err) {
      setError(err instanceof Error ? err.message : 'Failed to revoke session.');
    } finally {
      setWorking(false);
    }
  }

  function handleSignOut() {
    clearAuth();
    router.push('/login');
  }

  if (loading) {
    return (
      <AdminShell title="Security" subtitle="Loading…" nav={adminNav} activeHref="/security">
        <p>Loading sessions…</p>
      </AdminShell>
    );
  }

  return (
    <AdminShell
      title="Security"
      subtitle="Sessions, MFA login, and API token rotation"
      nav={adminNav}
      activeHref="/security"
      onSignOut={handleSignOut}
    >
      {error && <Alert>{error}</Alert>}

      <Card title="API token rotation">
        <p style={{ marginTop: 0, color: 'var(--color-text-secondary)' }}>
          Create a named token for integrations. Copy it once — it will not be shown again.
        </p>
        <form onSubmit={handleCreateToken} style={{ display: 'flex', gap: 12, flexWrap: 'wrap' }}>
          <Input
            label="Token name"
            value={tokenName}
            onChange={(e) => setTokenName(e.target.value)}
            required
          />
          <Button type="submit" disabled={working} style={{ alignSelf: 'flex-end' }}>
            {working ? 'Creating…' : 'Create token'}
          </Button>
        </form>
        {newPlainToken && (
          <Alert variant="success">
            New token (copy now):{' '}
            <code style={{ wordBreak: 'break-all' }}>{newPlainToken}</code>
          </Alert>
        )}
      </Card>

      <Card title="Active sessions">
        {sessions.length === 0 ? (
          <p>No active sessions.</p>
        ) : (
          <ul style={{ listStyle: 'none', padding: 0, margin: 0 }}>
            {sessions.map((session) => (
              <li
                key={session.id}
                style={{
                  display: 'flex',
                  justifyContent: 'space-between',
                  gap: 16,
                  padding: '12px 0',
                  borderBottom: '1px solid var(--color-border)',
                }}
              >
                <div>
                  <strong>{session.name}</strong>
                  {session.is_current ? ' (current)' : ''}
                  <div style={{ color: 'var(--color-text-secondary)', fontSize: 14 }}>
                    Created {session.created_at ?? '—'}
                    {session.last_used_at ? ` · Last used ${session.last_used_at}` : ''}
                  </div>
                </div>
                <Button
                  type="button"
                  disabled={working}
                  onClick={() => handleRevoke(session.id, session.is_current)}
                >
                  Revoke
                </Button>
              </li>
            ))}
          </ul>
        )}
      </Card>
    </AdminShell>
  );
}
