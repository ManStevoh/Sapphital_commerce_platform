'use client';

import Link from 'next/link';
import { FormEvent, useEffect, useState } from 'react';
import { useRouter } from 'next/navigation';
import { AdminShell, Alert, Button, Card, Input, Table, Td, Th } from '@sapphital/scp-ui';
import {
  clearAuth,
  createCollection,
  deleteCollection,
  fetchCollections,
  getStoredTenantId,
  getStoredToken,
  type CatalogCollection,
  type CollectionStatus,
  type CollectionType,
} from '@/lib/api';
import { adminNav } from '@/lib/nav';

export default function CollectionsPage() {
  const router = useRouter();
  const [collections, setCollections] = useState<CatalogCollection[]>([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const [working, setWorking] = useState(false);
  const [title, setTitle] = useState('');
  const [slug, setSlug] = useState('');
  const [type, setType] = useState<CollectionType>('smart');
  const [preset, setPreset] = useState<'new_arrivals' | 'on_sale' | 'best_sellers'>('new_arrivals');
  const [status, setStatus] = useState<CollectionStatus>('draft');

  useEffect(() => {
    const token = getStoredToken();
    const tenantId = getStoredTenantId();

    if (!token || !tenantId) {
      router.replace('/login');
      return;
    }

    fetchCollections(tenantId)
      .then(setCollections)
      .catch((err) => {
        setError(err instanceof Error ? err.message : 'Failed to load collections.');
      })
      .finally(() => {
        setLoading(false);
      });
  }, [router]);

  async function handleCreate(event: FormEvent<HTMLFormElement>) {
    event.preventDefault();
    const tenantId = getStoredTenantId();

    if (!tenantId || !title.trim()) {
      setError('Title is required.');
      return;
    }

    setWorking(true);
    setError(null);

    try {
      const collection = await createCollection(tenantId, {
        title: title.trim(),
        slug: slug.trim() || undefined,
        type,
        status,
        sort_order: type === 'manual' ? 'manual' : 'newest',
        rules_json:
          type === 'smart'
            ? {
                preset,
                days: 30,
              }
            : null,
        product_ids: type === 'manual' ? [] : undefined,
        published_at: status === 'published' ? new Date().toISOString() : null,
      });
      setCollections((current) =>
        [...current, collection].sort((a, b) => a.title.localeCompare(b.title)),
      );
      setTitle('');
      setSlug('');
      setStatus('draft');
    } catch (err) {
      setError(err instanceof Error ? err.message : 'Failed to create collection.');
    } finally {
      setWorking(false);
    }
  }

  async function handleDelete(collectionId: string) {
    const tenantId = getStoredTenantId();

    if (!tenantId || !window.confirm('Delete this collection?')) {
      return;
    }

    setWorking(true);
    setError(null);

    try {
      await deleteCollection(tenantId, collectionId);
      setCollections((current) => current.filter((item) => item.id !== collectionId));
    } catch (err) {
      setError(err instanceof Error ? err.message : 'Failed to delete collection.');
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
      <AdminShell title="Collections" subtitle="Loading…" nav={adminNav} activeHref="/collections">
        <p>Loading collections…</p>
      </AdminShell>
    );
  }

  return (
    <AdminShell
      title="Collections"
      subtitle={`${collections.length} total`}
      nav={adminNav}
      activeHref="/collections"
      onLogout={handleLogout}
    >
      {error && <Alert variant="error">{error}</Alert>}

      <Card title="New collection">
        <form onSubmit={handleCreate} style={{ display: 'grid', gap: '0.75rem', maxWidth: 480 }}>
          <Input
            label="Title"
            value={title}
            onChange={(event) => setTitle(event.target.value)}
            required
          />
          <Input
            label="Slug"
            value={slug}
            onChange={(event) => setSlug(event.target.value)}
            placeholder="optional"
          />
          <label style={{ display: 'grid', gap: '0.25rem' }}>
            <span>Type</span>
            <select
              value={type}
              onChange={(event) => setType(event.target.value as CollectionType)}
            >
              <option value="smart">Smart</option>
              <option value="manual">Manual</option>
            </select>
          </label>
          {type === 'smart' && (
            <label style={{ display: 'grid', gap: '0.25rem' }}>
              <span>Smart preset</span>
              <select
                value={preset}
                onChange={(event) =>
                  setPreset(event.target.value as 'new_arrivals' | 'on_sale' | 'best_sellers')
                }
              >
                <option value="new_arrivals">New arrivals (30 days)</option>
                <option value="on_sale">On sale (tag=sale)</option>
                <option value="best_sellers">Best sellers (30 days)</option>
              </select>
            </label>
          )}
          <label style={{ display: 'grid', gap: '0.25rem' }}>
            <span>Status</span>
            <select
              value={status}
              onChange={(event) => setStatus(event.target.value as CollectionStatus)}
            >
              <option value="draft">Draft</option>
              <option value="published">Published</option>
              <option value="scheduled">Scheduled</option>
            </select>
          </label>
          <Button type="submit" disabled={working}>
            {working ? 'Saving…' : 'Create collection'}
          </Button>
        </form>
      </Card>

      <Table>
        <thead>
          <tr>
            <Th>Title</Th>
            <Th>Type</Th>
            <Th>Status</Th>
            <Th>Actions</Th>
          </tr>
        </thead>
        <tbody>
          {collections.map((collection) => (
            <tr key={collection.id}>
              <Td>
                <Link href={`/collections/${collection.id}`}>{collection.title}</Link>
              </Td>
              <Td>{collection.type}</Td>
              <Td>{collection.status}</Td>
              <Td>
                <Button
                  type="button"
                  variant="secondary"
                  disabled={working}
                  onClick={() => handleDelete(collection.id)}
                >
                  Delete
                </Button>
              </Td>
            </tr>
          ))}
        </tbody>
      </Table>
    </AdminShell>
  );
}
