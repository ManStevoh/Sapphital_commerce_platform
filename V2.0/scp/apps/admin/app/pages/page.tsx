'use client';

import Link from 'next/link';
import { FormEvent, useEffect, useState } from 'react';
import { useRouter } from 'next/navigation';
import { AdminShell, Alert, Button, Card, Input, Table, Td, Th } from '@sapphital/scp-ui';
import {
  clearAuth,
  createCmsPage,
  deleteCmsPage,
  fetchCmsPages,
  getStoredTenantId,
  getStoredToken,
  type CmsPage,
} from '@/lib/api';
import { adminNav } from '@/lib/nav';

export default function PagesPage() {
  const router = useRouter();
  const [pages, setPages] = useState<CmsPage[]>([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const [working, setWorking] = useState(false);
  const [title, setTitle] = useState('');
  const [slug, setSlug] = useState('');
  const [publish, setPublish] = useState(false);

  useEffect(() => {
    const token = getStoredToken();
    const tenantId = getStoredTenantId();

    if (!token || !tenantId) {
      router.replace('/login');
      return;
    }

    fetchCmsPages(tenantId)
      .then(setPages)
      .catch((err) => {
        setError(err instanceof Error ? err.message : 'Failed to load pages.');
      })
      .finally(() => {
        setLoading(false);
      });
  }, [router]);

  async function handleCreate(event: FormEvent<HTMLFormElement>) {
    event.preventDefault();
    const tenantId = getStoredTenantId();

    if (!tenantId || !title.trim()) {
      return;
    }

    setWorking(true);
    setError(null);

    try {
      const page = await createCmsPage(tenantId, {
        title: title.trim(),
        slug: slug.trim() || undefined,
        status: publish ? 'published' : 'draft',
        published_at: publish ? new Date().toISOString() : null,
        body_json: {
          sections: [{ type: 'rich-text', content: '' }],
        },
      });
      setPages((current) => [...current, page].sort((a, b) => a.title.localeCompare(b.title)));
      setTitle('');
      setSlug('');
      setPublish(false);
    } catch (err) {
      setError(err instanceof Error ? err.message : 'Failed to create page.');
    } finally {
      setWorking(false);
    }
  }

  async function handleDelete(pageId: string) {
    const tenantId = getStoredTenantId();

    if (!tenantId || !window.confirm('Delete this page?')) {
      return;
    }

    setWorking(true);
    setError(null);

    try {
      await deleteCmsPage(tenantId, pageId);
      setPages((current) => current.filter((page) => page.id !== pageId));
    } catch (err) {
      setError(err instanceof Error ? err.message : 'Failed to delete page.');
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
      <AdminShell title="Pages" subtitle="Loading…" nav={adminNav} activeHref="/pages">
        <p>Loading pages…</p>
      </AdminShell>
    );
  }

  return (
    <AdminShell
      title="Pages"
      subtitle={`${pages.length} total`}
      nav={adminNav}
      activeHref="/pages"
      onSignOut={handleLogout}
    >
      {error && <Alert>{error}</Alert>}

      <Card title="Create page">
        <form onSubmit={handleCreate}>
          <Input
            label="Title"
            required
            value={title}
            onChange={(e) => setTitle(e.target.value)}
          />
          <Input
            label="Slug (optional)"
            value={slug}
            onChange={(e) => setSlug(e.target.value)}
            placeholder="about-us"
          />
          <label style={{ display: 'flex', alignItems: 'center', gap: '0.5rem', marginBottom: 12 }}>
            <input
              type="checkbox"
              checked={publish}
              onChange={(e) => setPublish(e.target.checked)}
            />
            Publish immediately
          </label>
          <Button type="submit" disabled={working}>
            {working ? 'Saving…' : 'Create page'}
          </Button>
        </form>
      </Card>

      <h2 style={{ marginTop: '1.5rem' }}>All pages</h2>
      {pages.length === 0 ? (
        <p>No pages yet. Create an About or Contact page to get started.</p>
      ) : (
        <Table aria-label="CMS pages">
          <thead>
            <tr>
              <Th>Title</Th>
              <Th>Slug</Th>
              <Th>Status</Th>
              <Th>Actions</Th>
            </tr>
          </thead>
          <tbody>
            {pages.map((page) => (
              <tr key={page.id}>
                <Td>{page.title}</Td>
                <Td>{page.slug}</Td>
                <Td>{page.status}</Td>
                <Td>
                  <Link href={`/pages/${page.id}`} style={{ marginRight: '0.75rem' }}>
                    Edit
                  </Link>
                  <Button
                    type="button"
                    variant="secondary"
                    disabled={working}
                    onClick={() => handleDelete(page.id)}
                  >
                    Delete
                  </Button>
                </Td>
              </tr>
            ))}
          </tbody>
        </Table>
      )}

      <p style={{ marginTop: '1rem' }}>
        <Link href="/products">← Back to products</Link>
      </p>
    </AdminShell>
  );
}
