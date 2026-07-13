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
  fetchCmsBlogPostVersions,
  fetchCmsBlogPosts,
  getStoredTenantId,
  getStoredToken,
  restoreCmsBlogPostVersion,
  updateCmsBlogPost,
  type CmsBlogPost,
  type CmsContentVersion,
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

export default function EditBlogPostPage() {
  const router = useRouter();
  const params = useParams<{ id: string }>();
  const postId = params.id;

  const [post, setPost] = useState<CmsBlogPost | null>(null);
  const [title, setTitle] = useState('');
  const [slug, setSlug] = useState('');
  const [authorName, setAuthorName] = useState('');
  const [excerpt, setExcerpt] = useState('');
  const [tagsInput, setTagsInput] = useState('');
  const [featuredImageUrl, setFeaturedImageUrl] = useState('');
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

  function applyPost(match: CmsBlogPost) {
    setPost(match);
    setTitle(match.title);
    setSlug(match.slug);
    setAuthorName(match.author_name);
    setExcerpt(match.excerpt ?? '');
    setTagsInput((match.tags ?? []).join(', '));
    setFeaturedImageUrl(match.featured_image_url ?? '');
    setSeoTitle(match.seo_title ?? '');
    setSeoDescription(match.seo_description ?? '');
    setSeoOgImageUrl(match.seo_og_image_url ?? '');
    setSeoCanonicalUrl(match.seo_canonical_url ?? '');
    setStatus(
      match.status === 'published'
        ? 'published'
        : match.status === 'scheduled'
          ? 'scheduled'
          : 'draft',
    );
    setScheduledPublishAt(toDatetimeLocal(match.scheduled_publish_at));
    setScheduledUnpublishAt(toDatetimeLocal(match.scheduled_unpublish_at));
    setBodyJson(normalizeBodyJson(match.body_json));
  }

  const refreshVersions = useCallback(async () => {
    const tenantId = getStoredTenantId();

    if (!tenantId || !postId) {
      return;
    }

    setVersionsLoading(true);
    setVersionsError(null);

    try {
      setVersions(await fetchCmsBlogPostVersions(tenantId, postId));
    } catch (err) {
      setVersionsError(err instanceof Error ? err.message : 'Failed to load versions.');
    } finally {
      setVersionsLoading(false);
    }
  }, [postId]);

  useEffect(() => {
    const token = getStoredToken();
    const tenantId = getStoredTenantId();

    if (!token || !tenantId) {
      router.replace('/login');
      return;
    }

    fetchCmsBlogPosts(tenantId)
      .then((posts) => {
        const match = posts.find((item) => item.id === postId);

        if (!match) {
          setError('Blog post not found.');
          return;
        }

        applyPost(match);
      })
      .catch((err) => {
        setError(err instanceof Error ? err.message : 'Failed to load blog post.');
      })
      .finally(() => {
        setLoading(false);
      });
  }, [postId, router]);

  useEffect(() => {
    if (post) {
      void refreshVersions();
    }
  }, [post?.id, refreshVersions]);

  async function handleSave(event: FormEvent<HTMLFormElement>) {
    event.preventDefault();
    const tenantId = getStoredTenantId();

    if (!tenantId || !post || !title.trim() || !authorName.trim()) {
      setError('Title and author are required.');
      return;
    }

    if (status === 'scheduled' && !scheduledPublishAt) {
      setError('Choose a publish time when status is scheduled.');
      return;
    }

    setWorking(true);
    setError(null);
    setSaved(false);

    const tags = tagsInput
      .split(',')
      .map((tag) => tag.trim())
      .filter(Boolean);

    try {
      const updated = await updateCmsBlogPost(tenantId, post.id, {
        title: title.trim(),
        slug: slug.trim() || undefined,
        author_name: authorName.trim(),
        excerpt: excerpt.trim() || null,
        tags,
        featured_image_url: featuredImageUrl.trim() || null,
        seo_title: seoTitle.trim() || null,
        seo_description: seoDescription.trim() || null,
        seo_og_image_url: seoOgImageUrl.trim() || null,
        seo_canonical_url: seoCanonicalUrl.trim() || null,
        status,
        published_at:
          status === 'published' ? post.published_at ?? new Date().toISOString() : null,
        scheduled_publish_at:
          status === 'scheduled' ? fromDatetimeLocal(scheduledPublishAt) : null,
        scheduled_unpublish_at:
          status === 'published' && scheduledUnpublishAt
            ? fromDatetimeLocal(scheduledUnpublishAt)
            : null,
        body_json: bodyJson,
      });
      applyPost(updated);
      setSaved(true);
      await refreshVersions();
    } catch (err) {
      setError(err instanceof Error ? err.message : 'Failed to save blog post.');
    } finally {
      setWorking(false);
    }
  }

  async function handleRestore(versionId: string) {
    const tenantId = getStoredTenantId();

    if (!tenantId || !post || !window.confirm('Restore this version? Current content will be snapshot first.')) {
      return;
    }

    setWorking(true);
    setError(null);

    try {
      const restored = await restoreCmsBlogPostVersion(tenantId, post.id, versionId);
      applyPost(restored);
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
      <AdminShell title="Edit post" subtitle="Loading…" nav={adminNav} activeHref="/blog">
        <p>Loading blog post…</p>
      </AdminShell>
    );
  }

  if (!post) {
    return (
      <AdminShell title="Edit post" nav={adminNav} activeHref="/blog" onSignOut={handleLogout}>
        {error && <Alert>{error}</Alert>}
        <p>
          <Link href="/blog">← Back to blog</Link>
        </p>
      </AdminShell>
    );
  }

  return (
    <AdminShell
      title="Edit post"
      subtitle={post.slug}
      nav={adminNav}
      activeHref="/blog"
      onSignOut={handleLogout}
    >
      {error && <Alert>{error}</Alert>}
      {saved && <Alert variant="success">Post saved.</Alert>}

      <form onSubmit={handleSave}>
        <Card title="Post details">
          <Input label="Title" required value={title} onChange={(e) => setTitle(e.target.value)} />
          <Input label="Slug" value={slug} onChange={(e) => setSlug(e.target.value)} />
          <Input
            label="Author"
            required
            value={authorName}
            onChange={(e) => setAuthorName(e.target.value)}
          />
          <Input label="Excerpt" value={excerpt} onChange={(e) => setExcerpt(e.target.value)} />
          <Input
            label="Tags (comma-separated)"
            value={tagsInput}
            onChange={(e) => setTagsInput(e.target.value)}
            placeholder="news, launch"
          />
          <Input
            label="Featured image URL"
            value={featuredImageUrl}
            onChange={(e) => setFeaturedImageUrl(e.target.value)}
          />
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
            placeholder="https://cdn.example.com/og-post.jpg"
          />
          <Input
            label="Canonical URL"
            value={seoCanonicalUrl}
            onChange={(e) => setSeoCanonicalUrl(e.target.value)}
            placeholder="https://yourstore.test/blog/launch-day"
          />
        </Card>

        <h2 style={{ marginTop: '1.5rem' }}>Content sections</h2>
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
            {working ? 'Saving…' : 'Save post'}
          </Button>
          <Link href="/blog">Cancel</Link>
        </div>
      </form>
    </AdminShell>
  );
}
