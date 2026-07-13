'use client';

import { useEffect, useState } from 'react';
import { useRouter } from 'next/navigation';
import { AdminShell, Alert, Button, Card, Table, Td, Th } from '@sapphital/scp-ui';
import {
  approveReturnRequest,
  clearAuth,
  fetchStoreSettings,
  getStoredTenantId,
  getStoredToken,
  listReturnRequests,
  receiveReturnRequest,
  rejectReturnRequest,
  shipReturnRequest,
  updateReturnWindowDays,
  type ReturnRequest,
} from '@/lib/api';
import { adminNav } from '@/lib/nav';

export default function ReturnsPage() {
  const router = useRouter();
  const [returns, setReturns] = useState<ReturnRequest[]>([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const [actionId, setActionId] = useState<string | null>(null);
  const [rejectReasonById, setRejectReasonById] = useState<Record<string, string>>({});
  const [returnWindowDays, setReturnWindowDays] = useState(14);
  const [savingWindow, setSavingWindow] = useState(false);
  const [settingsSuccess, setSettingsSuccess] = useState<string | null>(null);

  useEffect(() => {
    const token = getStoredToken();
    const tenantId = getStoredTenantId();

    if (!token || !tenantId) {
      router.replace('/login');
      return;
    }

    Promise.all([listReturnRequests(tenantId), fetchStoreSettings(tenantId)])
      .then(([returnList, settings]) => {
        setReturns(returnList);
        setReturnWindowDays(settings.return_window_days);
      })
      .catch((err) => {
        setError(err instanceof Error ? err.message : 'Failed to load returns.');
      })
      .finally(() => {
        setLoading(false);
      });
  }, [router]);

  async function handleSaveReturnWindow() {
    const tenantId = getStoredTenantId();

    if (!tenantId) {
      return;
    }

    setSavingWindow(true);
    setError(null);
    setSettingsSuccess(null);

    try {
      const settings = await updateReturnWindowDays(tenantId, returnWindowDays);
      setReturnWindowDays(settings.return_window_days);
      setSettingsSuccess('Return window updated.');
    } catch (err) {
      setError(err instanceof Error ? err.message : 'Failed to update return window.');
    } finally {
      setSavingWindow(false);
    }
  }

  async function runAction(returnId: string, action: () => Promise<ReturnRequest>) {
    setActionId(returnId);
    setError(null);

    try {
      const updated = await action();
      setReturns((current) =>
        current.map((item) => (item.id === updated.id ? updated : item)),
      );
    } catch (err) {
      setError(err instanceof Error ? err.message : 'Action failed.');
    } finally {
      setActionId(null);
    }
  }

  async function handleReject(returnId: string) {
    const tenantId = getStoredTenantId();
    const rejectionReason = rejectReasonById[returnId]?.trim();

    if (!tenantId || !rejectionReason) {
      setError('Rejection reason is required.');
      return;
    }

    setActionId(returnId);
    setError(null);

    try {
      const updated = await rejectReturnRequest(tenantId, returnId, rejectionReason);
      setReturns((current) =>
        current.map((item) => (item.id === updated.id ? updated : item)),
      );
      setRejectReasonById((current) => {
        const next = { ...current };
        delete next[returnId];
        return next;
      });
    } catch (err) {
      setError(err instanceof Error ? err.message : 'Failed to reject return.');
    } finally {
      setActionId(null);
    }
  }

  function handleLogout() {
    clearAuth();
    router.push('/login');
  }

  function renderActions(item: ReturnRequest) {
    const tenantId = getStoredTenantId();
    const busy = actionId === item.id;

    if (!tenantId) {
      return '—';
    }

    if (item.status === 'requested') {
      return (
        <Card>
          <div style={{ display: 'flex', flexDirection: 'column', gap: '0.5rem' }}>
            <Button
              type="button"
              disabled={busy}
              onClick={() =>
                runAction(item.id, () => approveReturnRequest(tenantId, item.id, false))
              }
            >
              {busy ? 'Working…' : 'Approve (physical)'}
            </Button>
            <Button
              type="button"
              variant="secondary"
              disabled={busy}
              onClick={() =>
                runAction(item.id, () => approveReturnRequest(tenantId, item.id, true))
              }
            >
              Approve & refund (digital)
            </Button>
            <input
              type="text"
              placeholder="Rejection reason"
              value={rejectReasonById[item.id] ?? ''}
              onChange={(event) =>
                setRejectReasonById((current) => ({
                  ...current,
                  [item.id]: event.target.value,
                }))
              }
            />
            <Button type="button" variant="secondary" disabled={busy} onClick={() => handleReject(item.id)}>
              Reject
            </Button>
          </div>
        </Card>
      );
    }

    if (item.status === 'approved') {
      return (
        <Button
          type="button"
          disabled={busy}
          onClick={() => runAction(item.id, () => shipReturnRequest(tenantId, item.id))}
        >
          Mark shipped
        </Button>
      );
    }

    if (item.status === 'shipped') {
      return (
        <Button
          type="button"
          disabled={busy}
          onClick={() => runAction(item.id, () => receiveReturnRequest(tenantId, item.id))}
        >
          Mark received & refund
        </Button>
      );
    }

    return item.rejection_reason ?? '—';
  }

  if (loading) {
    return (
      <AdminShell title="Returns" subtitle="Loading…" nav={adminNav} activeHref="/returns">
        <p>Loading returns…</p>
      </AdminShell>
    );
  }

  return (
    <AdminShell
      title="Returns"
      subtitle={`${returns.length} total`}
      nav={adminNav}
      activeHref="/returns"
      onSignOut={handleLogout}
    >
      {error && <Alert>{error}</Alert>}
      {settingsSuccess && <Alert variant="success">{settingsSuccess}</Alert>}

      <Card>
        <h2>Return policy</h2>
        <p>Customers can request returns within this many days after delivery (7–30).</p>
        <div style={{ display: 'flex', gap: '0.75rem', alignItems: 'center', marginTop: '0.75rem' }}>
          <label>
            Return window (days)
            <input
              type="number"
              min={7}
              max={30}
              value={returnWindowDays}
              onChange={(event) => setReturnWindowDays(Number(event.target.value))}
              style={{ display: 'block', marginTop: '0.25rem' }}
            />
          </label>
          <Button type="button" disabled={savingWindow} onClick={handleSaveReturnWindow}>
            {savingWindow ? 'Saving…' : 'Save policy'}
          </Button>
        </div>
      </Card>

      {returns.length === 0 ? (
        <p>No return requests yet.</p>
      ) : (
        <Table aria-label="Return request list">
          <thead>
            <tr>
              <Th>Status</Th>
              <Th>Order</Th>
              <Th>Reason</Th>
              <Th>Lines</Th>
              <Th>Requested</Th>
              <Th>Actions</Th>
            </tr>
          </thead>
          <tbody>
            {returns.map((item) => (
              <tr key={item.id}>
                <Td>{item.status}</Td>
                <Td>{item.order_id.slice(0, 8)}…</Td>
                <Td>{item.reason}</Td>
                <Td>{item.lines.length}</Td>
                <Td>{item.requested_at ? new Date(item.requested_at).toLocaleString() : '—'}</Td>
                <Td>{renderActions(item)}</Td>
              </tr>
            ))}
          </tbody>
        </Table>
      )}
    </AdminShell>
  );
}
