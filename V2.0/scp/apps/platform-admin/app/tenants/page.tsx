'use client';

import { useEffect, useState } from 'react';
import { useRouter } from 'next/navigation';
import { Alert, Button, PlatformShell, Table, Td, Th } from '@sapphital/scp-ui';
import {
  clearToken,
  fetchTenants,
  getStoredToken,
  updateTenantStatus,
  type Tenant,
} from '@/lib/api';

const platformNav = [{ href: '/tenants', label: 'Tenants' }];

export default function TenantsPage() {
  const router = useRouter();
  const [tenants, setTenants] = useState<Tenant[]>([]);
  const [total, setTotal] = useState(0);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const [actionId, setActionId] = useState<string | null>(null);

  useEffect(() => {
    const token = getStoredToken();

    if (!token) {
      router.replace('/login');
      return;
    }

    fetchTenants(token)
      .then((result) => {
        setTenants(result.data);
        setTotal(result.meta.total);
      })
      .catch((err) => {
        if (err instanceof Error && err.message.includes('401')) {
          clearToken();
          router.replace('/login');
          return;
        }
        setError(err instanceof Error ? err.message : 'Failed to load tenants.');
      })
      .finally(() => {
        setLoading(false);
      });
  }, [router]);

  async function handleToggleStatus(tenant: Tenant) {
    const token = getStoredToken();

    if (!token) {
      return;
    }

    const nextStatus = tenant.status === 'suspended' ? 'active' : 'suspended';
    setActionId(tenant.id);
    setError(null);

    try {
      const updated = await updateTenantStatus(token, tenant.id, nextStatus);
      setTenants((current) =>
        current.map((item) => (item.id === updated.id ? updated : item)),
      );
    } catch (err) {
      setError(err instanceof Error ? err.message : 'Failed to update tenant status.');
    } finally {
      setActionId(null);
    }
  }

  function handleLogout() {
    clearToken();
    router.push('/login');
  }

  if (loading) {
    return (
      <PlatformShell title="Tenants" subtitle="Loading…" nav={platformNav} activeHref="/tenants">
        <p>Loading tenants…</p>
      </PlatformShell>
    );
  }

  return (
    <PlatformShell
      title="Tenants"
      subtitle={`${total} total`}
      nav={platformNav}
      activeHref="/tenants"
      onSignOut={handleLogout}
    >
      {error && <Alert>{error}</Alert>}

      {tenants.length === 0 ? (
        <p>No tenants yet.</p>
      ) : (
        <Table aria-label="Tenant list">
          <thead>
            <tr>
              <Th>Name</Th>
              <Th>Slug</Th>
              <Th>Status</Th>
              <Th>Country</Th>
              <Th>Created</Th>
              <Th>Actions</Th>
            </tr>
          </thead>
          <tbody>
            {tenants.map((tenant) => (
              <tr key={tenant.id}>
                <Td>{tenant.name}</Td>
                <Td>{tenant.slug}</Td>
                <Td>{tenant.status}</Td>
                <Td>{tenant.country}</Td>
                <Td>
                  {tenant.created_at
                    ? new Date(tenant.created_at).toLocaleDateString('en-NG')
                    : '—'}
                </Td>
                <Td>
                  <Button
                    type="button"
                    variant={tenant.status === 'suspended' ? 'primary' : 'secondary'}
                    onClick={() => handleToggleStatus(tenant)}
                    disabled={actionId === tenant.id}
                  >
                    {actionId === tenant.id
                      ? 'Saving…'
                      : tenant.status === 'suspended'
                        ? 'Activate'
                        : 'Suspend'}
                  </Button>
                </Td>
              </tr>
            ))}
          </tbody>
        </Table>
      )}
    </PlatformShell>
  );
}
