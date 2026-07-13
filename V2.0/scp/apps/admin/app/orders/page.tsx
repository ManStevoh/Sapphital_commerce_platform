'use client';

import { useEffect, useState } from 'react';
import { useRouter } from 'next/navigation';
import { AdminShell, Alert, Button, Card, Table, Td, Th } from '@sapphital/scp-ui';
import {
  clearAuth,
  formatNgn,
  getStoredTenantId,
  getStoredToken,
  listOrders,
  refundOrder,
  type Order,
} from '@/lib/api';
import { adminNav } from '@/lib/nav';

export default function OrdersPage() {
  const router = useRouter();
  const [orders, setOrders] = useState<Order[]>([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const [actionId, setActionId] = useState<string | null>(null);
  const [partialAmountById, setPartialAmountById] = useState<Record<string, string>>({});
  const [reasonById, setReasonById] = useState<Record<string, string>>({});

  useEffect(() => {
    const token = getStoredToken();
    const tenantId = getStoredTenantId();

    if (!token || !tenantId) {
      router.replace('/login');
      return;
    }

    listOrders(tenantId)
      .then(setOrders)
      .catch((err) => {
        setError(err instanceof Error ? err.message : 'Failed to load orders.');
      })
      .finally(() => {
        setLoading(false);
      });
  }, [router]);

  async function handleRefund(order: Order, amountKobo?: number) {
    const tenantId = getStoredTenantId();

    if (!tenantId) {
      return;
    }

    setActionId(order.id);
    setError(null);

    try {
      const reason = reasonById[order.id]?.trim() || undefined;
      const result = await refundOrder(tenantId, order.id, {
        amount_kobo: amountKobo,
        reason,
      });

      setOrders((current) =>
        current.map((item) => (item.id === result.order.id ? result.order : item)),
      );
    } catch (err) {
      setError(err instanceof Error ? err.message : 'Refund failed.');
    } finally {
      setActionId(null);
    }
  }

  function handleLogout() {
    clearAuth();
    router.push('/login');
  }

  function renderRefundActions(order: Order) {
    if (order.status !== 'paid') {
      return '—';
    }

    const busy = actionId === order.id;
    const partialInput = partialAmountById[order.id] ?? '';
    const partialKobo = Math.round(Number(partialInput) * 100);

    return (
      <Card>
        <div style={{ display: 'flex', flexDirection: 'column', gap: '0.5rem' }}>
          <input
            type="text"
            placeholder="Reason (optional)"
            value={reasonById[order.id] ?? ''}
            onChange={(event) =>
              setReasonById((current) => ({
                ...current,
                [order.id]: event.target.value,
              }))
            }
          />
          <Button
            type="button"
            disabled={busy}
            onClick={() => handleRefund(order)}
          >
            {busy ? 'Processing…' : 'Full refund'}
          </Button>
          <input
            type="number"
            min={0.01}
            step={0.01}
            placeholder="Partial amount (NGN)"
            value={partialInput}
            onChange={(event) =>
              setPartialAmountById((current) => ({
                ...current,
                [order.id]: event.target.value,
              }))
            }
          />
          <Button
            type="button"
            variant="secondary"
            disabled={busy || !partialKobo || partialKobo < 1}
            onClick={() => handleRefund(order, partialKobo)}
          >
            Partial refund
          </Button>
        </div>
      </Card>
    );
  }

  if (loading) {
    return (
      <AdminShell title="Orders" subtitle="Loading…" nav={adminNav} activeHref="/orders">
        <p>Loading orders…</p>
      </AdminShell>
    );
  }

  return (
    <AdminShell
      title="Orders"
      subtitle={`${orders.length} total`}
      nav={adminNav}
      activeHref="/orders"
      onSignOut={handleLogout}
    >
      {error && <Alert>{error}</Alert>}

      {orders.length === 0 ? (
        <p>No orders yet.</p>
      ) : (
        <Table aria-label="Order list">
          <thead>
            <tr>
              <Th>Order #</Th>
              <Th>Status</Th>
              <Th>Total</Th>
              <Th>Items</Th>
              <Th>Customer</Th>
              <Th>Created</Th>
              <Th>Refund</Th>
            </tr>
          </thead>
          <tbody>
            {orders.map((order) => (
              <tr key={order.id}>
                <Td>{order.order_number}</Td>
                <Td>{order.status}</Td>
                <Td>{formatNgn(order.total_kobo)}</Td>
                <Td>{order.items.length}</Td>
                <Td>{order.customer_email ?? '—'}</Td>
                <Td>
                  {order.created_at
                    ? new Date(order.created_at).toLocaleString('en-NG')
                    : '—'}
                </Td>
                <Td>{renderRefundActions(order)}</Td>
              </tr>
            ))}
          </tbody>
        </Table>
      )}
    </AdminShell>
  );
}
