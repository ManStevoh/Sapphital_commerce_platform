'use client';

import Link from 'next/link';
import { FormEvent, useEffect, useState } from 'react';
import { useParams, useRouter } from 'next/navigation';
import { AdminShell, Alert, Button, Card, Input } from '@sapphital/scp-ui';
import {
  clearAuth,
  fetchProduct,
  getStoredTenantId,
  getStoredToken,
  updateProduct,
  type ProductInput,
} from '@/lib/api';
import { adminNav } from '@/lib/nav';

export default function EditProductPage() {
  const router = useRouter();
  const params = useParams<{ id: string }>();
  const productId = params.id;
  const [name, setName] = useState('');
  const [slug, setSlug] = useState('');
  const [priceNgn, setPriceNgn] = useState('');
  const [status, setStatus] = useState<'draft' | 'published'>('draft');
  const [inventoryQty, setInventoryQty] = useState('0');
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const [submitting, setSubmitting] = useState(false);

  useEffect(() => {
    const token = getStoredToken();
    const tenantId = getStoredTenantId();

    if (!token || !tenantId) {
      router.replace('/login');
      return;
    }

    fetchProduct(tenantId, productId)
      .then((product) => {
        setName(product.name);
        setSlug(product.slug);
        setPriceNgn((product.price_kobo / 100).toFixed(2));
        setStatus(product.status);
        setInventoryQty(String(product.inventory_qty));
      })
      .catch((err) => {
        setError(err instanceof Error ? err.message : 'Failed to load product.');
      })
      .finally(() => {
        setLoading(false);
      });
  }, [productId, router]);

  async function handleSubmit(event: FormEvent<HTMLFormElement>) {
    event.preventDefault();
    setError(null);
    setSubmitting(true);

    const tenantId = getStoredTenantId();

    if (!tenantId) {
      router.replace('/login');
      return;
    }

    const priceKobo = Math.round(parseFloat(priceNgn) * 100);

    if (Number.isNaN(priceKobo) || priceKobo < 0) {
      setError('Enter a valid price.');
      setSubmitting(false);
      return;
    }

    const input: ProductInput = {
      name,
      slug: slug.trim() || undefined,
      price_kobo: priceKobo,
      status,
      inventory_qty: parseInt(inventoryQty, 10) || 0,
    };

    try {
      await updateProduct(tenantId, productId, input);
      router.push('/products');
    } catch (err) {
      setError(err instanceof Error ? err.message : 'Failed to update product.');
    } finally {
      setSubmitting(false);
    }
  }

  function handleLogout() {
    clearAuth();
    router.push('/login');
  }

  if (loading) {
    return (
      <AdminShell title="Edit product" subtitle="Loading…" nav={adminNav} activeHref="/products">
        <p>Loading product…</p>
      </AdminShell>
    );
  }

  return (
    <AdminShell
      title="Edit product"
      nav={adminNav}
      activeHref="/products"
      onSignOut={handleLogout}
    >
      <p style={{ marginTop: 0 }}>
        <Link href="/products">&larr; Back to products</Link>
      </p>

      <Card>
        <form onSubmit={handleSubmit}>
          <Input label="Name" required value={name} onChange={(e) => setName(e.target.value)} />
          <Input label="Slug" value={slug} onChange={(e) => setSlug(e.target.value)} />
          <Input
            label="Price (NGN)"
            type="number"
            required
            min="0"
            step="0.01"
            value={priceNgn}
            onChange={(e) => setPriceNgn(e.target.value)}
          />
          <label style={{ display: 'block', marginBottom: 16 }}>
            <span style={{ display: 'block', marginBottom: 4, fontSize: '0.875rem' }}>Status</span>
            <select
              value={status}
              onChange={(e) => setStatus(e.target.value as 'draft' | 'published')}
              style={{
                width: '100%',
                padding: '8px 12px',
                borderRadius: 4,
                border: '1px solid var(--color-border)',
              }}
            >
              <option value="draft">Draft</option>
              <option value="published">Published</option>
            </select>
          </label>
          <Input
            label="Inventory quantity"
            type="number"
            required
            min="0"
            value={inventoryQty}
            onChange={(e) => setInventoryQty(e.target.value)}
          />

          {error && <Alert>{error}</Alert>}

          <Button type="submit" disabled={submitting}>
            {submitting ? 'Saving…' : 'Save changes'}
          </Button>
        </form>
      </Card>
    </AdminShell>
  );
}
