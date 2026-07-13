'use client';

import Link from 'next/link';
import { FormEvent, useEffect, useState } from 'react';
import { useRouter } from 'next/navigation';
import { AdminShell, Alert, Button, Card, Input } from '@sapphital/scp-ui';
import {
  clearAuth,
  createProduct,
  getStoredTenantId,
  getStoredToken,
  type ProductInput,
} from '@/lib/api';
import { adminNav } from '@/lib/nav';

export default function NewProductPage() {
  const router = useRouter();
  const [name, setName] = useState('');
  const [slug, setSlug] = useState('');
  const [priceNgn, setPriceNgn] = useState('');
  const [status, setStatus] = useState<'draft' | 'published'>('draft');
  const [inventoryQty, setInventoryQty] = useState('0');
  const [tagsInput, setTagsInput] = useState('');
  const [error, setError] = useState<string | null>(null);
  const [submitting, setSubmitting] = useState(false);

  useEffect(() => {
    if (!getStoredToken() || !getStoredTenantId()) {
      router.replace('/login');
    }
  }, [router]);

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
      price_kobo: priceKobo,
      status,
      inventory_qty: parseInt(inventoryQty, 10) || 0,
      tags: tagsInput
        .split(',')
        .map((tag) => tag.trim())
        .filter(Boolean),
    };

    if (slug.trim()) {
      input.slug = slug.trim();
    }

    try {
      await createProduct(tenantId, input);
      router.push('/products');
    } catch (err) {
      setError(err instanceof Error ? err.message : 'Failed to create product.');
    } finally {
      setSubmitting(false);
    }
  }

  function handleLogout() {
    clearAuth();
    router.push('/login');
  }

  return (
    <AdminShell
      title="New product"
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
          <Input
            label="Slug (optional)"
            value={slug}
            onChange={(e) => setSlug(e.target.value)}
          />
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
          <Input
            label="Tags (comma-separated)"
            value={tagsInput}
            onChange={(e) => setTagsInput(e.target.value)}
            placeholder="sale, electronics"
          />

          {error && <Alert>{error}</Alert>}

          <Button type="submit" disabled={submitting}>
            {submitting ? 'Creating…' : 'Create product'}
          </Button>
        </form>
      </Card>
    </AdminShell>
  );
}
