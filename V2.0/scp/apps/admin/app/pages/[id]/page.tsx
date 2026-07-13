'use client';

import Link from 'next/link';
import { FormEvent, useCallback, useEffect, useState } from 'react';
import { useParams, useRouter } from 'next/navigation';
import { AdminShell, Alert, Button, Card, Input } from '@sapphital/scp-ui';
import { CmsLivePreview } from '@/components/cms/CmsLivePreview';
import { SectionEditor } from '@/components/cms/SectionEditor';
import { VersionHistory } from '@/components/cms/VersionHistory';
import {
  clearAuth,
  fetchCmsPageVersions,
  fetchCmsPages,
  getStoredTenantId,
  getStoredToken,
  restoreCmsPageVersion,
  updateCmsPage,
  type CmsContentVersion,
  type CmsPage,
} from '@/lib/api';
import { normalizeBodyJson, type CmsBodyJson } from '@/lib/cms-sections';
import { adminNav } from '@/lib/nav';

function toDatetimeLocal(value: string | null | undefined): string {
  if (!value) {
    return '';
  }

  const date = new Date(value);

  if (Number.isNaN(date.getTime())) {
    return '';
  }

  const pad = (n: number) => String(n).padStart(2, '0');

  return `${date.getFullYear()}-${pad(date.getMonth() + 1)}-${pad(date.getDate())}T${pad(date.getHours())}:${pad(date.getMinutes())}`;
}

function fromDatetimeLocal(value: string): string {
  return new Date(value).toISOString();
}

function applyPageState(
  match: CmsPage,
  setters: {
    setPage: (page: CmsPage) => void;
    setTitle: (value: string) => void;
    setSlug: (value: string) => void;
    setSeoTitle: (value: string) => void;
    setSeoDescription: (value: string) => void;
    setSeoOgImageUrl: (value: string) => void;
    setSeoCanonicalUrl: (value: string) => void;
    setStatus: (value: 'draft' | 'published' | 'scheduled') => void;
    setScheduledPublishAt: (value: string) => void;
    setScheduledUnpublishAt: (value: string) => void;
    setBodyJson: (value: CmsBodyJson) => void;
  },
) {
  setters.setPage(match);
  setters.setTitle(match.title);
  setters.setSlug(match.slug);
  setters.setSeoTitle(match.seo_title ?? '');
  setters.setSeoDescription(match.seo_description ?? '');
  setters.setSeoOgImageUrl(match.seo_og_image_url ?? '');
  setters.setSeoCanonicalUrl(match.seo_canonical_url ?? '');
  setters.setStatus(
    match.status === 'published'
      ? 'published'
      : match.status === 'scheduled'
        ? 'scheduled'
        : 'draft',
  );
  setters.setScheduledPublishAt(toDatetimeLocal(match.scheduled_publish_at));
  setters.setScheduledUnpublishAt(toDatetimeLocal(match.scheduled_unpublish_at));
  setters.setBodyJson(normalizeBodyJson(match.body_json));
}

export default function EditPagePage() {
  const router = useRouter();
  const params = useParams<{ id: string }>();
  const pageId = params.id;

  const [page, setPage] = useState<CmsPage | null>(null);
  const [title, setTitle] = useState('');
  const [slug, setSlug] = useState('');
  const [seoTitle, setSeoTitle] = useState('');
  const [seoDescription, setSeoDescription] = useState('');
  const [seoOgImageUrl, setSeoOgImageUrl] = useState('');
  const [seoCanonicalUrl, setSeoCanonicalUrl] = useState('');
  const [status, setStatus] = useState<'draft' | 'published' | 'scheduled'>('draft');
  const [scheduledPublishAt, setScheduledPublishAt] = useState('');
  const [scheduledUnpublishAt, setScheduledUnpublishAt] = useState('');
  const [bodyJson, setBodyJson] = useState<CmsBodyJson>({ sections: [{ type: 'rich-text', content: '' }] });
  const [loading, setLoading] = useState(true);
  const [working, setWorking] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [saved, setSaved] = useState(false);
  const [versions, setVersions] = useState<CmsContentVersion[]>([]);
  const [versionsLoading, setVersionsLoading] = useState(false);
  const [versionsError, setVersionsError] = useState<string | null>(null);

  const stateSetters = {
    setPage,
    setTitle,
    setSlug,
    setSeoTitle,
    setSeoDescription,
    setSeoOgImageUrl,
    setSeoCanonicalUrl,
    setStatus,
    setScheduledPublishAt,
    setScheduledUnpublishAt,
    setBodyJson,
  };

  const refreshVersions = useCallback(async () => {
    const tenantId = getStoredTenantId();

    if (!tenantId || !pageId) {
      return;
    }

    setVersionsLoading(true);
    setVersionsError(null);

    try {
      setVersions(await fetchCmsPageVersions(tenantId, pageId));
    } catch (err) {
      setVersionsError(err instanceof Error ? err.message : 'Failed to load versions.');
    } finally {
      setVersionsLoading(false);
    }
  }, [pageId]);

  useEffect(() => {
    const token = getStoredToken();
    const tenantId = getStoredTenantId();

    if (!token || !tenantId) {
      router.replace('/login');
      return;
    }

    fetchCmsPages(tenantId)
      .then((pages) => {
        const match = pages.find((item) => item.id === pageId);

        if (!match) {
          setError('Page not found.');
          return;
        }

        applyPageState(match, stateSetters);
      })
      .catch((err) => {
        setError(err instanceof Error ? err.message : 'Failed to load page.');
      })
      .finally(() => {
        setLoading(false);
      });
  }, [pageId, router]);

  useEffect(() => {
    if (page) {
      void refreshVersions();
    }
  }, [page?.id, refreshVersions]);

  async function handleSave(event: FormEvent<HTMLFormElement>) {
    event.preventDefault();
    const tenantId = getStoredTenantId();

    if (!tenantId || !page || !title.trim()) {
      return;
    }

    if (status === 'scheduled' && !scheduledPublishAt) {
      setError('Choose a publish time when status is scheduled.');
      return;
    }

    setWorking(true);
    setError(null);
    setSaved(false);

    try {
      const updated = await updateCmsPage(tenantId, page.id, {
        title: title.trim(),
        slug: slug.trim() || undefined,
        seo_title: seoTitle.trim() || null,
        seo_description: seoDescription.trim() || null,
        seo_og_image_url: seoOgImageUrl.trim() || null,
        seo_canonical_url: seoCanonicalUrl.trim() || null,
        status,
        published_at:
          status === 'published' ? page.published_at ?? new Date().toISOString() : null,
        scheduled_publish_at:
          status === 'scheduled' ? fromDatetimeLocal(scheduledPublishAt) : null,
        scheduled_unpublish_at:
          status === 'published' && scheduledUnpublishAt
            ? fromDatetimeLocal(scheduledUnpublishAt)
            : null,
        body_json: bodyJson,
      });
      applyPageState(updated, stateSetters);
      setSaved(true);
      await refreshVersions();
    } catch (err) {
      setError(err instanceof Error ? err.message : 'Failed to save page.');
    } finally {
      setWorking(false);
    }
  }

  async function handleRestore(versionId: string) {
    const tenantId = getStoredTenantId();

    if (!tenantId || !page || !window.confirm('Restore this version? Current content will be snapshot first.')) {
      return;
    }

    setWorking(true);
    setError(null);

    try {
      const restored = await restoreCmsPageVersion(tenantId, page.id, versionId);
      applyPageState(restored, stateSetters);
      setSaved(true);
      await refreshVersions();
    } catch (err) {
      setError(err instanceof Error ? err.message : 'Failed to restore version.');
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
      <AdminShell title="Edit page" subtitle="Loading…" nav={adminNav} activeHref="/pages">
        <p>Loading page…</p>
      </AdminShell>
    );
  }

  if (!page) {
    return (
      <AdminShell title="Edit page" nav={adminNav} activeHref="/pages" onSignOut={handleLogout}>
        {error && <Alert>{error}</Alert>}
        <p>
          <Link href="/pages">← Back to pages</Link>
        </p>
      </AdminShell>
    );
  }

  return (
    <AdminShell
      title="Edit page"
      subtitle={page.slug}
      nav={adminNav}
      activeHref="/pages"
      onSignOut={handleLogout}
    >
      {error && <Alert>{error}</Alert>}
      {saved && <Alert variant="success">Page saved.</Alert>}

      <form onSubmit={handleSave}>
        <Card title="Page details">
          <Input label="Title" required value={title} onChange={(e) => setTitle(e.target.value)} />
          <Input label="Slug" value={slug} onChange={(e) => setSlug(e.target.value)} />
          <label style={{ display: 'block', marginBottom: 12 }}>
            <span style={{ display: 'block', marginBottom: 4, fontWeight: 600 }}>Status</span>
            <select
              value={status}
              onChange={(e) => setStatus(e.target.value as 'draft' | 'published' | 'scheduled')}
              style={{ padding: 8, minWidth: 160 }}
            >
              <option value="draft">Draft</option>
              <option value="published">Published</option>
              <option value="scheduled">Scheduled</option>
            </select>
          </label>
          {status === 'scheduled' && (
            <Input
              label="Publish at"
              type="datetime-local"
              required
              value={scheduledPublishAt}
              onChange={(e) => setScheduledPublishAt(e.target.value)}
            />
          )}
          {status === 'published' && (
            <Input
              label="Unpublish at (optional)"
              type="datetime-local"
              value={scheduledUnpublishAt}
              onChange={(e) => setScheduledUnpublishAt(e.target.value)}
            />
          )}
        </Card>

        <Card title="SEO">
          <Input label="Meta title" value={seoTitle} onChange={(e) => setSeoTitle(e.target.value)} />
          <Input
            label="Meta description"
            value={seoDescription}
            onChange={(e) => setSeoDescription(e.target.value)}
          />
          <Input
            label="OG image URL"
            value={seoOgImageUrl}
            onChange={(e) => setSeoOgImageUrl(e.target.value)}
            placeholder="https://cdn.example.com/og-about.jpg"
          />
          <Input
            label="Canonical URL"
            value={seoCanonicalUrl}
            onChange={(e) => setSeoCanonicalUrl(e.target.value)}
            placeholder="https://yourstore.test/pages/about"
          />
        </Card>

        <h2 style={{ marginTop: '1.5rem' }}>Sections</h2>
        <SectionEditor value={bodyJson} onChange={setBodyJson} disabled={working} />

        <div style={{ marginTop: '1.5rem' }}>
          <CmsLivePreview title={title} body={bodyJson} />
        </div>

        <div style={{ marginTop: '1.5rem' }}>
          <VersionHistory
            versions={versions}
            loading={versionsLoading}
            working={working}
            error={versionsError}
            onRefresh={() => void refreshVersions()}
            onRestore={handleRestore}
          />
        </div>

        <div style={{ marginTop: '1.5rem', display: 'flex', gap: '0.75rem' }}>
          <Button type="submit" disabled={working}>
            {working ? 'Saving…' : 'Save page'}
          </Button>
          <Link href="/pages">Cancel</Link>
        </div>
      </form>
    </AdminShell>
  );
}
