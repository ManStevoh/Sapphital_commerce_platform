import { headers } from 'next/headers';
import { fetchBlogFeedXml } from '@/lib/api';

export async function GET() {
  const requestHeaders = await headers();
  const tenantSlug = requestHeaders.get('x-tenant-slug') ?? undefined;
  const xml = await fetchBlogFeedXml(tenantSlug);

  if (!xml) {
    return new Response('Feed unavailable.', { status: 503 });
  }

  return new Response(xml, {
    headers: {
      'Content-Type': 'application/rss+xml; charset=UTF-8',
      'Cache-Control': 'public, max-age=300',
    },
  });
}
