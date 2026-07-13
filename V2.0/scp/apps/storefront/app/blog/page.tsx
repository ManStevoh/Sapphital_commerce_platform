import type { Metadata } from 'next';
import Link from 'next/link';
import { headers } from 'next/headers';
import { StoreHeader } from '@/components/theme/StoreHeader';
import { fetchPublishedBlogPostPage, fetchStoreNavigation } from '@/lib/api';
import { loadStorefrontTheme } from '@/lib/theme-loader';

interface BlogIndexPageProps {
  searchParams?: Promise<{ cursor?: string }>;
}

export async function generateMetadata(): Promise<Metadata> {
  const requestHeaders = await headers();
  const storeName = requestHeaders.get('x-tenant-name') ?? 'Store';

  return {
    title: `Blog — ${storeName}`,
    description: `News and updates from ${storeName}`,
  };
}

export default async function BlogIndexPage({ searchParams }: BlogIndexPageProps) {
  const requestHeaders = await headers();
  const query = await searchParams;
  const cursor = typeof query?.cursor === 'string' ? query.cursor : undefined;
  const tenantSlug = requestHeaders.get('x-tenant-slug');
  const storeName = requestHeaders.get('x-tenant-name') ?? 'Store';
  const themeBundle = await loadStorefrontTheme();
  const [postPage, navLinks] = await Promise.all([
    fetchPublishedBlogPostPage(tenantSlug ?? undefined, { limit: 10, cursor }),
    fetchStoreNavigation('header', tenantSlug ?? undefined),
  ]);
  const posts = postPage.data;

  return (
    <main style={{ maxWidth: 720, margin: '0 auto', padding: '2rem 1rem' }}>
      <StoreHeader
        storeName={storeName}
        tenantSlug={tenantSlug}
        theme={themeBundle?.config ?? null}
        navLinks={navLinks}
      />

      <p>
        <Link href="/">&larr; Back to shop</Link>
      </p>

      <h1>Blog</h1>
      <p style={{ fontSize: '0.875rem' }}>
        <Link href="/blog/feed.xml">RSS feed</Link>
      </p>

      {posts.length === 0 ? (
        <p>No posts published yet. Check back soon.</p>
      ) : (
        <ul style={{ listStyle: 'none', padding: 0, margin: 0 }}>
          {posts.map((post) => (
            <li
              key={post.id}
              style={{
                borderBottom: '1px solid var(--color-border)',
                padding: '1.25rem 0',
              }}
            >
              <h2 style={{ margin: '0 0 0.5rem', fontSize: '1.25rem' }}>
                <Link href={`/blog/${post.slug}`}>{post.title}</Link>
              </h2>
              <p style={{ margin: '0 0 0.5rem', color: 'var(--color-text-secondary)', fontSize: '0.875rem' }}>
                {post.author_name}
                {post.published_at
                  ? ` · ${new Date(post.published_at).toLocaleDateString()}`
                  : ''}
              </p>
              {post.excerpt && (
                <p style={{ margin: 0, lineHeight: 1.6 }}>{post.excerpt}</p>
              )}
            </li>
          ))}
        </ul>
      )}

      {(cursor || postPage.meta.next_cursor) && (
        <nav
          aria-label="Blog pagination"
          style={{ display: 'flex', gap: '1rem', marginTop: '1.5rem' }}
        >
          {cursor && <Link href="/blog">Latest posts</Link>}
          {postPage.meta.next_cursor && (
            <Link href={`/blog?cursor=${encodeURIComponent(postPage.meta.next_cursor)}`}>
              Older posts
            </Link>
          )}
        </nav>
      )}
    </main>
  );
}
