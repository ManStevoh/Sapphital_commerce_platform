import type { Metadata } from 'next';
import Link from 'next/link';
import { headers } from 'next/headers';
import { notFound } from 'next/navigation';
import { CmsSectionRenderer } from '@/components/cms/CmsSectionRenderer';
import { StoreHeader } from '@/components/theme/StoreHeader';
import {
  blogBodyText,
  fetchBlogPostBySlug,
  fetchRelatedBlogPosts,
  fetchStoreNavigation,
} from '@/lib/api';
import { buildCmsMetadata, siteBaseUrl } from '@/lib/seo-metadata';
import { loadStorefrontTheme } from '@/lib/theme-loader';

interface BlogPostPageProps {
  params: Promise<{ slug: string }>;
}

export async function generateMetadata({ params }: BlogPostPageProps): Promise<Metadata> {
  const { slug } = await params;
  const requestHeaders = await headers();
  const storeName = requestHeaders.get('x-tenant-name') ?? 'Store';
  const tenantSlug = requestHeaders.get('x-tenant-slug') ?? undefined;
  const post = await fetchBlogPostBySlug(slug, tenantSlug);

  if (!post) {
    return { title: `Post not found — ${storeName}` };
  }

  return buildCmsMetadata({
    title: post.title,
    slug: post.slug,
    storeName,
    tenantSlug,
    pathPrefix: '/blog',
    description: post.excerpt,
    seo_title: post.seo_title,
    seo_description: post.seo_description,
    seo_og_image_url: post.seo_og_image_url,
    seo_canonical_url: post.seo_canonical_url,
    fallbackOgImage: post.featured_image_url,
  });
}

export default async function BlogPostPage({ params }: BlogPostPageProps) {
  const { slug } = await params;
  const requestHeaders = await headers();
  const tenantSlug = requestHeaders.get('x-tenant-slug');
  const storeName = requestHeaders.get('x-tenant-name') ?? 'Store';
  const themeBundle = await loadStorefrontTheme();
  const [post, navLinks] = await Promise.all([
    fetchBlogPostBySlug(slug, tenantSlug ?? undefined),
    fetchStoreNavigation('header', tenantSlug ?? undefined),
  ]);

  if (!post) {
    notFound();
  }

  const relatedPosts = await fetchRelatedBlogPosts(post.id, tenantSlug ?? undefined);
  const sections = post.body_json?.sections ?? [];
  const hasRenderableSections = sections.length > 0;
  const fallbackBody = blogBodyText(post);
  const baseUrl = siteBaseUrl(tenantSlug);
  const postUrl = `${baseUrl}/blog/${post.slug}`;

  const articleJsonLd = {
    '@context': 'https://schema.org',
    '@type': 'Article',
    headline: post.title,
    description: post.excerpt ?? post.seo_description ?? undefined,
    author: {
      '@type': 'Person',
      name: post.author_name,
    },
    datePublished: post.published_at ?? undefined,
    image: post.seo_og_image_url ?? post.featured_image_url ?? undefined,
    url: post.seo_canonical_url ?? postUrl,
    mainEntityOfPage: post.seo_canonical_url ?? postUrl,
  };

  const breadcrumbJsonLd = {
    '@context': 'https://schema.org',
    '@type': 'BreadcrumbList',
    itemListElement: [
      {
        '@type': 'ListItem',
        position: 1,
        name: storeName,
        item: baseUrl,
      },
      {
        '@type': 'ListItem',
        position: 2,
        name: 'Blog',
        item: `${baseUrl}/blog`,
      },
      {
        '@type': 'ListItem',
        position: 3,
        name: post.title,
        item: postUrl,
      },
    ],
  };

  return (
    <main style={{ maxWidth: 720, margin: '0 auto', padding: '2rem 1rem' }}>
      <script
        type="application/ld+json"
        dangerouslySetInnerHTML={{ __html: JSON.stringify(articleJsonLd) }}
      />
      <script
        type="application/ld+json"
        dangerouslySetInnerHTML={{ __html: JSON.stringify(breadcrumbJsonLd) }}
      />

      <StoreHeader
        storeName={storeName}
        tenantSlug={tenantSlug}
        theme={themeBundle?.config ?? null}
        navLinks={navLinks}
      />

      <nav aria-label="Breadcrumb" style={{ fontSize: '0.875rem', marginBottom: '1rem' }}>
        <Link href="/">Shop</Link>
        {' / '}
        <Link href="/blog">Blog</Link>
        {' / '}
        <span>{post.title}</span>
      </nav>

      <article>
        <h1 style={{ marginBottom: '0.5rem' }}>{post.title}</h1>
        <p style={{ color: 'var(--color-text-secondary)', fontSize: '0.875rem' }}>
          {post.author_name}
          {post.published_at
            ? ` · ${new Date(post.published_at).toLocaleDateString()}`
            : ''}
        </p>

        {post.tags && post.tags.length > 0 && (
          <p style={{ fontSize: '0.875rem', color: 'var(--color-text-muted)' }}>
            {post.tags.map((tag) => `#${tag}`).join(' ')}
          </p>
        )}

        {post.featured_image_url && (
          // eslint-disable-next-line @next/next/no-img-element
          <img
            src={post.featured_image_url}
            alt={post.title}
            style={{ width: '100%', borderRadius: 8, marginTop: '1.25rem' }}
          />
        )}

        <div style={{ marginTop: '1.5rem' }}>
          {hasRenderableSections ? (
            <CmsSectionRenderer sections={sections} />
          ) : (
            <div style={{ lineHeight: 1.7, whiteSpace: 'pre-wrap' }}>{fallbackBody}</div>
          )}
        </div>
      </article>

      {relatedPosts.length > 0 && (
        <section style={{ marginTop: '2.5rem' }}>
          <h2>Related posts</h2>
          <ul style={{ listStyle: 'none', padding: 0, margin: 0 }}>
            {relatedPosts.map((relatedPost) => (
              <li
                key={relatedPost.id}
                style={{
                  borderTop: '1px solid var(--color-border, #e5e7eb)',
                  padding: '1rem 0',
                }}
              >
                <h3 style={{ margin: '0 0 0.375rem', fontSize: '1rem' }}>
                  <Link href={`/blog/${relatedPost.slug}`}>{relatedPost.title}</Link>
                </h3>
                {relatedPost.excerpt && (
                  <p style={{ margin: 0, color: 'var(--color-text-secondary)', lineHeight: 1.6 }}>
                    {relatedPost.excerpt}
                  </p>
                )}
              </li>
            ))}
          </ul>
        </section>
      )}
    </main>
  );
}
