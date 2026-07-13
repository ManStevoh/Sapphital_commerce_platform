'use client';

import { useEffect, useState } from 'react';
import { useRouter } from 'next/navigation';
import { AdminShell, Alert, Button, Table, Td, Th } from '@sapphital/scp-ui';
import {
  clearAuth,
  formatNgn,
  getStoredTenantId,
  getStoredToken,
  listDisputes,
  resolveDispute,
  type Dispute,
} from '@/lib/api';
import { adminNav } from '@/lib/nav';

export default function DisputesPage() {
  const router = useRouter();
  const [disputes, setDisputes] = useState<Dispute[]>([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const [actionId, setActionId] = useState<string | null>(null);

  useEffect(() => {
    const token = getStoredToken();
    const tenantId = getStoredTenantId();

    if (!token || !tenantId) {
      router.replace('/login');
      return;
    }

    listDisputes(tenantId)
      .then(setDisputes)
      .catch((err) => {
        setError(err instanceof Error ? err.message : 'Failed to load disputes.');
      })
      .finally(() => {
        setLoading(false);
      });
  }, [router]);

  async function handleResolve(
    disputeId: string,
    status: 'won' | 'lost' | 'withdrawn',
  ) {
    const tenantId = getStoredTenantId();

    if (!tenantId) {
      return;
    }

    setActionId(disputeId);
    setError(null);

    try {
      const updated = await resolveDispute(tenantId, disputeId, status);
      setDisputes((current) =>
        current.map((item) => (item.id === updated.id ? updated : item)),
      );
    } catch (err) {
      setError(err instanceof Error ? err.message : 'Failed to resolve dispute.');
    } finally {
      setActionId(null);
    }
  }

  function handleLogout() {
    clearAuth();
    router.push('/login');
  }

  if (loading) {
    return (
      <AdminShell title="Disputes" subtitle="Loading…" nav={adminNav} activeHref="/disputes">
        <p>Loading disputes…</p>
      </AdminShell>
    );
  }

  return (
    <AdminShell
      title="Disputes"
      subtitle={`${disputes.length} total`}
      nav={adminNav}
      activeHref="/disputes"
      onSignOut={handleLogout}
    >
      {error && <Alert>{error}</Alert>}

      {disputes.length === 0 ? (
        <p>No chargeback disputes yet.</p>
      ) : (
        <Table aria-label="Dispute list">
          <thead>
            <tr>
              <Th>Status</Th>
              <Th>Case ID</Th>
              <Th>Order</Th>
              <Th>Amount</Th>
              <Th>Due</Th>
              <Th>Actions</Th>
            </tr>
          </thead>
          <tbody>
            {disputes.map((dispute) => (
              <tr key={dispute.id}>
                <Td>{dispute.status}</Td>
                <Td>{dispute.provider_case_id}</Td>
                <Td>{dispute.order_id.slice(0, 8)}…</Td>
                <Td>{formatNgn(dispute.amount_kobo)}</Td>
                <Td>{dispute.due_at ? new Date(dispute.due_at).toLocaleString() : '—'}</Td>
                <Td>
                  {dispute.status === 'open' || dispute.status === 'under_review' ? (
                    <div style={{ display: 'flex', gap: '0.5rem', flexWrap: 'wrap' }}>
                      <Button
                        type="button"
                        disabled={actionId === dispute.id}
                        onClick={() => handleResolve(dispute.id, 'won')}
                      >
                        Won
                      </Button>
                      <Button
                        type="button"
                        variant="secondary"
                        disabled={actionId === dispute.id}
                        onClick={() => handleResolve(dispute.id, 'lost')}
                      >
                        Lost
                      </Button>
                      <Button
                        type="button"
                        variant="secondary"
                        disabled={actionId === dispute.id}
                        onClick={() => handleResolve(dispute.id, 'withdrawn')}
                      >
                        Withdrawn
                      </Button>
                    </div>
                  ) : (
                    dispute.resolved_at
                      ? new Date(dispute.resolved_at).toLocaleString()
                      : '—'
                  )}
                </Td>
              </tr>
            ))}
          </tbody>
        </Table>
      )}
    </AdminShell>
  );
}
