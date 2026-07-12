'use client';

import { useEffect, useState } from 'react';
import { useRouter } from 'next/navigation';
import { AdminShell, Alert, Button, Card, Table, Td, Th } from '@sapphital/scp-ui';
import {
  clearAuth,
  createShipmentFromOrder,
  getStoredTenantId,
  getStoredToken,
  listOrders,
  listShipments,
  markShipmentDelivered,
  markShipmentShipped,
  type Order,
  type Shipment,
} from '@/lib/api';
import { adminNav } from '@/lib/nav';

export default function ShipmentsPage() {
  const router = useRouter();
  const [shipments, setShipments] = useState<Shipment[]>([]);
  const [orders, setOrders] = useState<Order[]>([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const [selectedOrderId, setSelectedOrderId] = useState('');
  const [creating, setCreating] = useState(false);
  const [actionId, setActionId] = useState<string | null>(null);
  const [trackingById, setTrackingById] = useState<Record<string, string>>({});

  useEffect(() => {
    const token = getStoredToken();
    const tenantId = getStoredTenantId();

    if (!token || !tenantId) {
      router.replace('/login');
      return;
    }

    Promise.all([listShipments(tenantId), listOrders(tenantId)])
      .then(([shipmentList, orderList]) => {
        setShipments(shipmentList);
        setOrders(orderList.filter((order) => order.status === 'paid'));
      })
      .catch((err) => {
        setError(err instanceof Error ? err.message : 'Failed to load shipments.');
      })
      .finally(() => {
        setLoading(false);
      });
  }, [router]);

  async function handleCreateShipment() {
    const tenantId = getStoredTenantId();

    if (!tenantId || !selectedOrderId) {
      return;
    }

    setCreating(true);
    setError(null);

    try {
      const shipment = await createShipmentFromOrder(tenantId, selectedOrderId);
      setShipments((current) => [shipment, ...current]);
      setSelectedOrderId('');
    } catch (err) {
      setError(err instanceof Error ? err.message : 'Failed to create shipment.');
    } finally {
      setCreating(false);
    }
  }

  async function handleShip(shipmentId: string) {
    const tenantId = getStoredTenantId();
    const trackingNumber = trackingById[shipmentId]?.trim();

    if (!tenantId || !trackingNumber) {
      setError('Tracking number is required to mark as shipped.');
      return;
    }

    setActionId(shipmentId);
    setError(null);

    try {
      const updated = await markShipmentShipped(tenantId, shipmentId, trackingNumber);
      setShipments((current) =>
        current.map((shipment) => (shipment.id === updated.id ? updated : shipment)),
      );
    } catch (err) {
      setError(err instanceof Error ? err.message : 'Failed to mark shipment as shipped.');
    } finally {
      setActionId(null);
    }
  }

  async function handleDeliver(shipmentId: string) {
    const tenantId = getStoredTenantId();

    if (!tenantId) {
      return;
    }

    setActionId(shipmentId);
    setError(null);

    try {
      const updated = await markShipmentDelivered(tenantId, shipmentId);
      setShipments((current) =>
        current.map((shipment) => (shipment.id === updated.id ? updated : shipment)),
      );
    } catch (err) {
      setError(err instanceof Error ? err.message : 'Failed to mark shipment as delivered.');
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
      <AdminShell title="Shipments" subtitle="Loading…" nav={adminNav} activeHref="/shipments">
        <p>Loading shipments…</p>
      </AdminShell>
    );
  }

  return (
    <AdminShell
      title="Shipments"
      subtitle={`${shipments.length} total`}
      nav={adminNav}
      activeHref="/shipments"
      onSignOut={handleLogout}
    >
      {error && <Alert>{error}</Alert>}

      <Card title="Create from paid order" style={{ marginBottom: 24 }}>
        <div style={{ display: 'flex', gap: 12, flexWrap: 'wrap', alignItems: 'flex-end' }}>
          <label style={{ flex: 1, minWidth: 220 }}>
            <span style={{ display: 'block', marginBottom: 4, fontSize: '0.875rem' }}>
              Paid order
            </span>
            <select
              value={selectedOrderId}
              onChange={(event) => setSelectedOrderId(event.target.value)}
              aria-label="Select paid order"
              style={{
                width: '100%',
                padding: '8px 12px',
                borderRadius: 4,
                border: '1px solid var(--color-border)',
              }}
            >
              <option value="">Select order…</option>
              {orders.map((order) => (
                <option key={order.id} value={order.id}>
                  {order.order_number} — {order.customer_email ?? 'no email'}
                </option>
              ))}
            </select>
          </label>
          <Button
            type="button"
            onClick={handleCreateShipment}
            disabled={!selectedOrderId || creating}
          >
            {creating ? 'Creating…' : 'Create shipment'}
          </Button>
        </div>
      </Card>

      {shipments.length === 0 ? (
        <p>No shipments yet.</p>
      ) : (
        <Table aria-label="Shipment list">
          <thead>
            <tr>
              <Th>Status</Th>
              <Th>Order</Th>
              <Th>Tracking</Th>
              <Th>Items</Th>
              <Th>Actions</Th>
            </tr>
          </thead>
          <tbody>
            {shipments.map((shipment) => (
              <tr key={shipment.id}>
                <Td>{shipment.status}</Td>
                <Td>{shipment.order_id.slice(0, 8)}…</Td>
                <Td>
                  {shipment.tracking_number ?? (
                    <input
                      type="text"
                      placeholder="Tracking #"
                      value={trackingById[shipment.id] ?? ''}
                      onChange={(event) =>
                        setTrackingById((current) => ({
                          ...current,
                          [shipment.id]: event.target.value,
                        }))
                      }
                      aria-label={`Tracking number for shipment ${shipment.id}`}
                      style={{
                        width: '100%',
                        padding: '8px 12px',
                        borderRadius: 4,
                        border: '1px solid var(--color-border)',
                      }}
                    />
                  )}
                </Td>
                <Td>{shipment.lines.length}</Td>
                <Td>
                  {shipment.status === 'pending' && (
                    <Button
                      type="button"
                      onClick={() => handleShip(shipment.id)}
                      disabled={actionId === shipment.id}
                      style={{ marginRight: 8 }}
                    >
                      {actionId === shipment.id ? 'Saving…' : 'Mark shipped'}
                    </Button>
                  )}
                  {shipment.status === 'shipped' && (
                    <Button
                      type="button"
                      onClick={() => handleDeliver(shipment.id)}
                      disabled={actionId === shipment.id}
                    >
                      {actionId === shipment.id ? 'Saving…' : 'Mark delivered'}
                    </Button>
                  )}
                  {shipment.status === 'delivered' && shipment.delivered_at && (
                    <span>
                      Delivered {new Date(shipment.delivered_at).toLocaleString('en-NG')}
                    </span>
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
