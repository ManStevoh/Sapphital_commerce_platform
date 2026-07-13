'use client';

import Link from 'next/link';
import { FormEvent, useEffect, useState } from 'react';
import { useRouter } from 'next/navigation';
import { AdminShell, Alert, Button, Card, Input, Table, Td, Th } from '@sapphital/scp-ui';
import {
  clearAuth,
  createCmsBlogPost,
  deleteCmsBlogPost,
  fetchCmsBlogPosts,
  getStoredTenantId,
  getStoredToken,
  type CmsBlogPost,
} from '@/lib/api';
import { adminNav } from '@/lib/nav';

export default function BlogPage() {
  const router = useRouter();
  const [posts, setPosts] = useState<CmsBlogPost[]>([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const [working, setWorking] = useState(false);
  const [title, setTitle] = useState('');
  const [slug, setSlug] = useState('');
  const [authorName, setAuthorName] = useState('');
  const [excerpt, setExcerpt] = useState('');
  const [publish, setPublish] = useState(false);

  useEffect(() => {
    const token = getStoredToken();
    const tenantId = getStoredTenantId();

    if (!token || !tenantId) {
      router.replace('/login');
      return;
    }

    fetchCmsBlogPosts(tenantId)
      .then(setPosts)
      .catch((err) => {
        setError(err instanceof Error ? err.message : 'Failed to load blog posts.');
      })
      .finally(() => {
        setLoading(false);
      });
  }, [router]);

  async function handleCreate(event: FormEvent<HTMLFormElement>) {
    event.preventDefault();
    const tenantId = getStoredTenantId();

    if (!tenantId || !title.trim() || !authorName.trim()) {
      setError('Title and author are required.');
      return;
    }

    setWorking(true);
    setError(null);

    try {
      const post = await createCmsBlogPost(tenantId, {
        title: title.trim(),
        slug: slug.trim() || undefined,
        author_name: authorName.trim(),
        excerpt: excerpt.trim() || undefined,
        status: publish ? 'published' : 'draft',
        published_at: publish ? new Date().toISOString() : null,
        body_json: {
          sections: [{ type: 'rich-text', content: excerpt.trim() || title.trim() }],
        },
      });
      setPosts((current) =>
        [post, ...current].sort((a, b) => {
          const aDate = a.published_at ?? '';
          const bDate = b.published_at ?? '';
          return bDate.localeCompare(aDate);
        }),
      );
      setTitle('');
      setSlug('');
      setExcerpt('');
      setPublish(false);
    } catch (err) {
      setError(err instanceof Error ? err.message : 'Failed to create post.');
    } finally {
      setWorking(false);
    }
  }

  async function handleDelete(postId: string) {
    const tenantId = getStoredTenantId();

    if (!tenantId || !window.confirm('Delete this blog post?')) {
      return;
    }

    setWorking(true);
    setError(null);

    try {
      await deleteCmsBlogPost(tenantId, postId);
      setPosts((current) => current.filter((post) => post.id !== postId));
    } catch (err) {
      setError(err instanceof Error ? err.message : 'Failed to delete post.');
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
      <AdminShell title="Blog" subtitle="Loading…" nav={adminNav} activeHref="/blog">
        <p>Loading blog posts…</p>
      </AdminShell>
    );
  }

  return (
    <AdminShell
      title="Blog"
      subtitle={`${posts.length} posts`}
      nav={adminNav}
      activeHref="/blog"
      onSignOut={handleLogout}
    >
      {error && <Alert>{error}</Alert>}

      <Card title="New post">
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
            placeholder="launch-day"
          />
          <Input
            label="Author"
            required
            value={authorName}
            onChange={(e) => setAuthorName(e.target.value)}
          />
          <Input
            label="Excerpt"
            value={excerpt}
            onChange={(e) => setExcerpt(e.target.value)}
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
            {working ? 'Saving…' : 'Create post'}
          </Button>
        </form>
      </Card>

      <h2 style={{ marginTop: '1.5rem' }}>All posts</h2>
      {posts.length === 0 ? (
        <p>No blog posts yet.</p>
      ) : (
        <Table aria-label="Blog posts">
          <thead>
            <tr>
              <Th>Title</Th>
              <Th>Author</Th>
              <Th>Status</Th>
              <Th>Published</Th>
              <Th>Actions</Th>
            </tr>
          </thead>
          <tbody>
            {posts.map((post) => (
              <tr key={post.id}>
                <Td>{post.title}</Td>
                <Td>{post.author_name}</Td>
                <Td>{post.status}</Td>
                <Td>
                  {post.published_at
                    ? new Date(post.published_at).toLocaleDateString()
                    : '—'}
                </Td>
                <Td>
                  <Link href={`/blog/${post.id}`} style={{ marginRight: '0.75rem' }}>
                    Edit
                  </Link>
                  <Button
                    type="button"
                    variant="secondary"
                    disabled={working}
                    onClick={() => handleDelete(post.id)}
                  >
                    Delete
                  </Button>
                </Td>
              </tr>
            ))}
          </tbody>
        </Table>
      )}
    </AdminShell>
  );
}
