'use client';

import Link from 'next/link';
import { FormEvent, useEffect, useMemo, useState } from 'react';
import { useParams, useRouter } from 'next/navigation';
import { AdminShell, Alert, Button, Card, Input } from '@sapphital/scp-ui';
import {
  clearAuth,
  fetchCollection,
  fetchCollectionProducts,
  fetchProducts,
  generateCollectionDescription,
  getStoredTenantId,
  getStoredToken,
  syncCollectionProducts,
  updateCollection,
  type CatalogCollection,
  type CollectionStatus,
  type CollectionType,
  type Product,
} from '@/lib/api';
import { adminNav } from '@/lib/nav';

export default function CollectionEditPage() {
  const router = useRouter();
  const params = useParams<{ id: string }>();
  const collectionId = params.id;

  const [collection, setCollection] = useState<CatalogCollection | null>(null);
  const [products, setProducts] = useState<Product[]>([]);
  const [selectedProductIds, setSelectedProductIds] = useState<string[]>([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const [working, setWorking] = useState(false);

  const [title, setTitle] = useState('');
  const [slug, setSlug] = useState('');
  const [description, setDescription] = useState('');
  const [type, setType] = useState<CollectionType>('manual');
  const [status, setStatus] = useState<CollectionStatus>('draft');
  const [sortOrder, setSortOrder] = useState('manual');
  const [aiWorking, setAiWorking] = useState(false);
  const [preset, setPreset] = useState('new_arrivals');
  const [startsAt, setStartsAt] = useState('');
  const [endsAt, setEndsAt] = useState('');

  useEffect(() => {
    const token = getStoredToken();
    const tenantId = getStoredTenantId();

    if (!token || !tenantId || !collectionId) {
      router.replace('/login');
      return;
    }

    Promise.all([
      fetchCollection(tenantId, collectionId),
      fetchProducts(tenantId),
      fetchCollectionProducts(tenantId, collectionId).catch(() => [] as Product[]),
    ])
      .then(([loaded, catalogProducts, membership]) => {
        setCollection(loaded);
        setProducts(catalogProducts);
        setTitle(loaded.title);
        setSlug(loaded.slug);
        setDescription(loaded.description ?? '');
        setType(loaded.type);
        setStatus(loaded.status);
        setSortOrder(loaded.sort_order);
        setStartsAt(loaded.starts_at ? loaded.starts_at.slice(0, 16) : '');
        setEndsAt(loaded.ends_at ? loaded.ends_at.slice(0, 16) : '');
        const rules = loaded.rules_json ?? {};
        if (typeof rules.preset === 'string') {
          setPreset(rules.preset);
        }
        setSelectedProductIds(membership.map((product) => product.id));
      })
      .catch((err) => {
        setError(err instanceof Error ? err.message : 'Failed to load collection.');
      })
      .finally(() => {
        setLoading(false);
      });
  }, [collectionId, router]);

  const publishedProducts = useMemo(
    () => products.filter((product) => product.status === 'published'),
    [products],
  );

  async function handleSave(event: FormEvent<HTMLFormElement>) {
    event.preventDefault();
    const tenantId = getStoredTenantId();

    if (!tenantId || !collectionId || !title.trim()) {
      setError('Title is required.');
      return;
    }

    setWorking(true);
    setError(null);

    try {
      const updated = await updateCollection(tenantId, collectionId, {
        title: title.trim(),
        slug: slug.trim() || undefined,
        description: description.trim() || null,
        type,
        status,
        sort_order: sortOrder as CatalogCollection['sort_order'],
        starts_at: startsAt ? new Date(startsAt).toISOString() : null,
        ends_at: endsAt ? new Date(endsAt).toISOString() : null,
        published_at: status === 'published' ? new Date().toISOString() : null,
        rules_json:
          type === 'smart'
            ? {
                preset,
                days: 30,
              }
            : null,
        product_ids: type === 'manual' ? selectedProductIds : undefined,
      });

      setCollection(updated);

      if (type === 'manual') {
        await syncCollectionProducts(tenantId, collectionId, selectedProductIds);
      }
    } catch (err) {
      setError(err instanceof Error ? err.message : 'Failed to save collection.');
    } finally {
      setWorking(false);
    }
  }

  async function handleGenerateDescription() {
    const tenantId = getStoredTenantId();

    if (!tenantId || !title.trim()) {
      setError('Title is required before generating a description.');
      return;
    }

    setAiWorking(true);
    setError(null);

    try {
      const draft = await generateCollectionDescription(tenantId, {
        title: title.trim(),
        type,
        rules:
          type === 'smart'
            ? {
                preset,
                days: 30,
              }
            : null,
      });
      setDescription(draft.draft);
    } catch (err) {
      setError(err instanceof Error ? err.message : 'AI generation failed.');
    } finally {
      setAiWorking(false);
    }
  }

  function toggleProduct(productId: string) {
    setSelectedProductIds((current) =>
      current.includes(productId)
        ? current.filter((id) => id !== productId)
        : [...current, productId],
    );
  }

  function handleLogout() {
    clearAuth();
    router.push('/login');
  }

  if (loading) {
    return (
      <AdminShell title="Collection" subtitle="Loading…" nav={adminNav} activeHref="/collections">
        <p>Loading collection…</p>
      </AdminShell>
    );
  }

  return (
    <AdminShell
      title={collection?.title ?? 'Collection'}
      subtitle={collection?.slug}
      nav={adminNav}
      activeHref="/collections"
      onLogout={handleLogout}
    >
      <p>
        <Link href="/collections">&larr; Back to collections</Link>
      </p>

      {error && <Alert variant="error">{error}</Alert>}

      <Card title="Edit collection">
        <form onSubmit={handleSave} style={{ display: 'grid', gap: '0.75rem', maxWidth: 560 }}>
          <Input label="Title" value={title} onChange={(event) => setTitle(event.target.value)} required />
          <Input label="Slug" value={slug} onChange={(event) => setSlug(event.target.value)} />
          <label style={{ display: 'grid', gap: '0.25rem' }}>
            <span>Description</span>
            <textarea
              value={description}
              onChange={(event) => setDescription(event.target.value)}
              rows={3}
            />
          </label>
          <Button
            type="button"
            variant="secondary"
            disabled={aiWorking || working}
            onClick={handleGenerateDescription}
          >
            {aiWorking ? 'Generating…' : 'Generate description'}
          </Button>
          <label style={{ display: 'grid', gap: '0.25rem' }}>
            <span>Type</span>
            <select value={type} onChange={(event) => setType(event.target.value as CollectionType)}>
              <option value="manual">Manual</option>
              <option value="smart">Smart</option>
            </select>
          </label>
          {type === 'smart' && (
            <label style={{ display: 'grid', gap: '0.25rem' }}>
              <span>Smart preset</span>
              <select value={preset} onChange={(event) => setPreset(event.target.value)}>
                <option value="new_arrivals">New arrivals</option>
                <option value="on_sale">On sale</option>
                <option value="best_sellers">Best sellers</option>
              </select>
            </label>
          )}
          <label style={{ display: 'grid', gap: '0.25rem' }}>
            <span>Sort order</span>
            <select value={sortOrder} onChange={(event) => setSortOrder(event.target.value)}>
              <option value="manual">Manual</option>
              <option value="newest">Newest</option>
              <option value="price_asc">Price ascending</option>
              <option value="price_desc">Price descending</option>
              <option value="best_selling">Best selling</option>
            </select>
          </label>
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
          <Input
            label="Starts at"
            type="datetime-local"
            value={startsAt}
            onChange={(event) => setStartsAt(event.target.value)}
          />
          <Input
            label="Ends at"
            type="datetime-local"
            value={endsAt}
            onChange={(event) => setEndsAt(event.target.value)}
          />

          {type === 'manual' && (
            <fieldset style={{ border: '1px solid var(--color-border)', padding: '0.75rem' }}>
              <legend>Products</legend>
              <div style={{ display: 'grid', gap: '0.35rem', maxHeight: 240, overflow: 'auto' }}>
                {publishedProducts.map((product) => (
                  <label key={product.id} style={{ display: 'flex', gap: '0.5rem' }}>
                    <input
                      type="checkbox"
                      checked={selectedProductIds.includes(product.id)}
                      onChange={() => toggleProduct(product.id)}
                    />
                    <span>
                      {product.name} ({product.slug})
                    </span>
                  </label>
                ))}
              </div>
            </fieldset>
          )}

          <Button type="submit" disabled={working}>
            {working ? 'Saving…' : 'Save collection'}
          </Button>
        </form>
      </Card>
    </AdminShell>
  );
}
