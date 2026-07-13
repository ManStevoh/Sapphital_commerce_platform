'use client';

import Link from 'next/link';
import { FormEvent, useEffect, useState } from 'react';
import { useRouter } from 'next/navigation';
import { Alert, Button, Card, Input } from '@sapphital/scp-ui';
import {
  createCustomerAddress,
  customerLogout,
  deleteCustomerAddress,
  fetchCustomerAddresses,
  fetchCustomerOrders,
  formatNgn,
  type CustomerAddress,
  type CustomerOrder,
} from '@/lib/api';
import {
  clearCustomerSession,
  getCustomerEmail,
  getCustomerToken,
} from '@/lib/customer-auth';
import { resolveClientTenantId } from '@/lib/tenant-client';

export default function CustomerAccountPage() {
  const router = useRouter();
  const [email, setEmail] = useState<string | null>(null);
  const [orders, setOrders] = useState<CustomerOrder[]>([]);
  const [addresses, setAddresses] = useState<CustomerAddress[]>([]);
  const [error, setError] = useState<string | null>(null);
  const [loading, setLoading] = useState(true);
  const [line1, setLine1] = useState('');
  const [city, setCity] = useState('');
  const [state, setState] = useState('Lagos');
  const [working, setWorking] = useState(false);

  useEffect(() => {
    const token = getCustomerToken();

    if (!token) {
      router.replace('/account/login');
      return;
    }

    setEmail(getCustomerEmail());

    async function load() {
      try {
        const tenantId = await resolveClientTenantId();
        const [orderData, addressData] = await Promise.all([
          fetchCustomerOrders(tenantId, token),
          fetchCustomerAddresses(tenantId, token),
        ]);
        setOrders(orderData);
        setAddresses(addressData);
      } catch (err) {
        setError(err instanceof Error ? err.message : 'Failed to load account.');
      } finally {
        setLoading(false);
      }
    }

    void load();
  }, [router]);

  async function handleLogout() {
    const token = getCustomerToken();
    const tenantId = await resolveClientTenantId().catch(() => null);

    if (token && tenantId) {
      try {
        await customerLogout(tenantId, token);
      } catch {
        // Still clear local session.
      }
    }

    clearCustomerSession();
    router.push('/');
  }

  async function handleAddAddress(event: FormEvent<HTMLFormElement>) {
    event.preventDefault();
    const token = getCustomerToken();

    if (!token) {
      return;
    }

    setWorking(true);
    setError(null);

    try {
      const tenantId = await resolveClientTenantId();
      const created = await createCustomerAddress(tenantId, token, {
        line1: line1.trim(),
        city: city.trim(),
        state,
        is_default: addresses.length === 0,
      });
      setAddresses((current) => [created, ...current]);
      setLine1('');
      setCity('');
    } catch (err) {
      setError(err instanceof Error ? err.message : 'Failed to save address.');
    } finally {
      setWorking(false);
    }
  }

  async function handleDeleteAddress(id: string) {
    const token = getCustomerToken();

    if (!token) {
      return;
    }

    setWorking(true);
    setError(null);

    try {
      const tenantId = await resolveClientTenantId();
      await deleteCustomerAddress(tenantId, token, id);
      setAddresses((current) => current.filter((address) => address.id !== id));
    } catch (err) {
      setError(err instanceof Error ? err.message : 'Failed to delete address.');
    } finally {
      setWorking(false);
    }
  }

  if (loading) {
    return (
      <main style={{ maxWidth: 720, margin: '2rem auto', padding: '0 1rem' }}>
        <p>Loading account…</p>
      </main>
    );
  }

  return (
    <main style={{ maxWidth: 720, margin: '2rem auto', padding: '0 1rem' }}>
      <p>
        <Link href="/">&larr; Shop</Link>
        {' · '}
        <Link href="/wishlist">Wishlist</Link>
      </p>
      <header
        style={{
          display: 'flex',
          justifyContent: 'space-between',
          alignItems: 'center',
          gap: 16,
          marginBottom: 24,
        }}
      >
        <div>
          <h1 style={{ margin: 0 }}>My account</h1>
          <p style={{ margin: '4px 0 0', color: 'var(--color-text-secondary)' }}>{email}</p>
        </div>
        <Button type="button" variant="secondary" onClick={handleLogout}>
          Sign out
        </Button>
      </header>

      {error && <Alert>{error}</Alert>}

      <Card title="Orders">
        {orders.length === 0 ? (
          <p style={{ marginTop: 0 }}>No orders yet.</p>
        ) : (
          <ul style={{ listStyle: 'none', padding: 0, margin: 0 }}>
            {orders.map((order) => (
              <li
                key={order.id}
                style={{
                  borderTop: '1px solid var(--color-border, #e5e7eb)',
                  padding: '0.75rem 0',
                }}
              >
                <strong>{order.order_number}</strong> · {order.status} · {formatNgn(order.total_kobo)}
              </li>
            ))}
          </ul>
        )}
      </Card>

      <Card title="Address book">
        <form onSubmit={handleAddAddress} style={{ marginBottom: 16 }}>
          <Input label="Address line" required value={line1} onChange={(e) => setLine1(e.target.value)} />
          <Input label="City" required value={city} onChange={(e) => setCity(e.target.value)} />
          <Input label="State" required value={state} onChange={(e) => setState(e.target.value)} />
          <Button type="submit" disabled={working}>
            Save address
          </Button>
        </form>
        {addresses.length === 0 ? (
          <p>No saved addresses.</p>
        ) : (
          <ul style={{ listStyle: 'none', padding: 0, margin: 0 }}>
            {addresses.map((address) => (
              <li
                key={address.id}
                style={{
                  borderTop: '1px solid var(--color-border, #e5e7eb)',
                  padding: '0.75rem 0',
                  display: 'flex',
                  justifyContent: 'space-between',
                  gap: 12,
                }}
              >
                <span>
                  {address.line1}, {address.city}, {address.state}
                  {address.is_default ? ' (default)' : ''}
                </span>
                <Button
                  type="button"
                  variant="secondary"
                  disabled={working}
                  onClick={() => handleDeleteAddress(address.id)}
                >
                  Remove
                </Button>
              </li>
            ))}
          </ul>
        )}
      </Card>
    </main>
  );
}
