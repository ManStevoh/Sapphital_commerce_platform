'use client';

import { FormEvent, useEffect, useState } from 'react';
import { useRouter } from 'next/navigation';
import { AdminShell, Alert, Button, Card, Input } from '@sapphital/scp-ui';
import {
  clearAuth,
  createCustomDomain,
  deleteCustomDomain,
  fetchCustomDomains,
  getStoredTenantId,
  getStoredToken,
  verifyCustomDomain,
  type CustomDomainRecord,
} from '@/lib/api';
import { adminNav } from '@/lib/nav';

export default function CustomDomainsPage() {
  const router = useRouter();
  const [domains, setDomains] = useState<CustomDomainRecord[]>([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const [working, setWorking] = useState(false);
  const [domainInput, setDomainInput] = useState('');

  useEffect(() => {
    const token = getStoredToken();
    const tenantId = getStoredTenantId();

    if (!token || !tenantId) {
      router.replace('/login');
      return;
    }

    fetchCustomDomains(tenantId)
      .then(setDomains)
      .catch((err) => {
        setError(err instanceof Error ? err.message : 'Failed to load domains.');
      })
      .finally(() => setLoading(false));
  }, [router]);

  async function handleCreate(event: FormEvent<HTMLFormElement>) {
    event.preventDefault();
    const tenantId = getStoredTenantId();

    if (!tenantId || !domainInput.trim()) {
      return;
    }

    setWorking(true);
    setError(null);

    try {
      const created = await createCustomDomain(tenantId, domainInput.trim());
      setDomains((current) => [created, ...current]);
      setDomainInput('');
    } catch (err) {
      setError(err instanceof Error ? err.message : 'Failed to add domain.');
    } finally {
      setWorking(false);
    }
  }

  async function handleVerify(id: string) {
    const tenantId = getStoredTenantId();

    if (!tenantId) {
      return;
    }

    setWorking(true);
    setError(null);

    try {
      const updated = await verifyCustomDomain(tenantId, id);
      setDomains((current) => current.map((item) => (item.id === id ? updated : item)));
    } catch (err) {
      setError(err instanceof Error ? err.message : 'Verification failed.');
    } finally {
      setWorking(false);
    }
  }

  async function handleDelete(id: string) {
    const tenantId = getStoredTenantId();

    if (!tenantId || !window.confirm('Remove this custom domain?')) {
      return;
    }

    setWorking(true);
    setError(null);

    try {
      await deleteCustomDomain(tenantId, id);
      setDomains((current) => current.filter((item) => item.id !== id));
    } catch (err) {
      setError(err instanceof Error ? err.message : 'Failed to remove domain.');
    } finally {
      setWorking(false);
    }
  }

  if (loading) {
    return (
      <AdminShell title="Custom domains" subtitle="Loading…" nav={adminNav} activeHref="/domains">
        <p>Loading…</p>
      </AdminShell>
    );
  }

  return (
    <AdminShell
      title="Custom domains"
      subtitle="DNS · SSL · growth entitlement"
      nav={adminNav}
      activeHref="/domains"
      onLogout={() => {
        clearAuth();
        router.push('/login');
      }}
    >
      {error && <Alert variant="error">{error}</Alert>}

      <Card title="Add domain">
        <form onSubmit={handleCreate}>
          <Input
            label="Hostname"
            value={domainInput}
            onChange={(e) => setDomainInput(e.target.value)}
            placeholder="www.merchant.ng"
            required
          />
          <Button type="submit" disabled={working}>
            Add domain
          </Button>
        </form>
      </Card>

      {domains.map((domain) => (
        <Card key={domain.id} title={domain.domain}>
          <p style={{ marginTop: 0 }}>
            Status: <strong>{domain.status}</strong>
            {domain.is_primary ? ' · primary' : ''}
          </p>
          <p style={{ fontSize: '0.875rem', color: 'var(--color-text-secondary)' }}>
            TXT {domain.dns.txt_host} = {domain.dns.txt_value}
            <br />
            CNAME {domain.dns.cname_host} → {domain.dns.cname_target}
          </p>
          <div style={{ display: 'flex', gap: 8 }}>
            <Button
              type="button"
              variant="secondary"
              disabled={working || domain.status === 'active'}
              onClick={() => handleVerify(domain.id)}
            >
              Verify DNS / SSL
            </Button>
            <Button type="button" variant="secondary" disabled={working} onClick={() => handleDelete(domain.id)}>
              Remove
            </Button>
          </div>
        </Card>
      ))}

      {domains.length === 0 && <p>No custom domains yet. Growth plan or higher required.</p>}
    </AdminShell>
  );
}
