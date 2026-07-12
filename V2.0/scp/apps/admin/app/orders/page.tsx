'use client';

import { useEffect, useState } from 'react';
import { useRouter } from 'next/navigation';
import { AdminShell, Alert, Table, Td, Th } from '@sapphital/scp-ui';
import {
  clearAuth,
  formatNgn,
  getStoredTenantId,
  getStoredToken,
  listOrders,
  type Order,
} from '@/lib/api';
import { adminNav } from '@/lib/nav';

export default function OrdersPage() {
  const router = useRouter();
  const [orders, setOrders] = useState<Order[]>([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);

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

  function handleLogout() {
    clearAuth();
    router.push('/login');
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
              </tr>
            ))}
          </tbody>
        </Table>
      )}
    </AdminShell>
  );
}
