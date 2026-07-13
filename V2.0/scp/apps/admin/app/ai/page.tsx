'use client';

import { useEffect, useState } from 'react';
import { useRouter } from 'next/navigation';
import { AdminShell, Alert, Button, Card, Table, Td, Th } from '@sapphital/scp-ui';
import {
  clearAuth,
  fetchAiUsage,
  getStoredTenantId,
  getStoredToken,
  updateAiSettings,
  type AiUsageSummary,
} from '@/lib/api';
import { adminNav } from '@/lib/nav';

export default function AiAdminPage() {
  const router = useRouter();
  const [usage, setUsage] = useState<AiUsageSummary | null>(null);
  const [aiEnabled, setAiEnabled] = useState(true);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const [working, setWorking] = useState(false);

  useEffect(() => {
    const token = getStoredToken();
    const tenantId = getStoredTenantId();

    if (!token || !tenantId) {
      router.replace('/login');
      return;
    }

    fetchAiUsage(tenantId)
      .then(setUsage)
      .catch((err) => {
        setError(err instanceof Error ? err.message : 'Failed to load AI usage.');
      })
      .finally(() => {
        setLoading(false);
      });
  }, [router]);

  async function handleToggle() {
    const tenantId = getStoredTenantId();

    if (!tenantId) {
      return;
    }

    setWorking(true);
    setError(null);

    try {
      const next = !aiEnabled;
      const result = await updateAiSettings(tenantId, { ai_enabled: next });
      setAiEnabled(result.ai_enabled);
    } catch (err) {
      setError(err instanceof Error ? err.message : 'Failed to update AI settings.');
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
      <AdminShell title="AI" subtitle="Loading…" nav={adminNav} activeHref="/ai">
        <p>Loading AI usage…</p>
      </AdminShell>
    );
  }

  const featureRows = Object.entries(usage?.by_feature ?? {});

  return (
    <AdminShell
      title="AI"
      subtitle="Usage · opt-out"
      nav={adminNav}
      activeHref="/ai"
      onLogout={handleLogout}
    >
      {error && <Alert variant="error">{error}</Alert>}

      <Card title="Settings">
        <p style={{ marginTop: 0 }}>
          AI features are {aiEnabled ? 'enabled' : 'disabled'} for this store.
        </p>
        <Button type="button" variant="secondary" disabled={working} onClick={handleToggle}>
          {aiEnabled ? 'Disable AI features' : 'Enable AI features'}
        </Button>
      </Card>

      <Card title="Usage this month">
        <p style={{ marginTop: 0 }}>
          Requests: {usage?.requests ?? 0} · Tokens: {usage?.tokens ?? 0}
        </p>
        <Table>
          <thead>
            <tr>
              <Th>Feature</Th>
              <Th>Requests</Th>
              <Th>Tokens</Th>
            </tr>
          </thead>
          <tbody>
            {featureRows.map(([feature, row]) => (
              <tr key={feature}>
                <Td>{feature}</Td>
                <Td>{row.requests}</Td>
                <Td>{row.tokens}</Td>
              </tr>
            ))}
          </tbody>
        </Table>
        {featureRows.length === 0 && <p>No AI usage recorded this month.</p>}
      </Card>
    </AdminShell>
  );
}
