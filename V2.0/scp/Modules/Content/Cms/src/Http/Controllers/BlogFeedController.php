<?php

declare(strict_types=1);

namespace Modules\Content\Cms\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Modules\Content\Cms\Enums\ContentStatus;
use Modules\Content\Cms\Models\BlogPost;
use Platform\Tenancy\Models\Tenant;

final class BlogFeedController
{
    public function rss(Request $request): Response
    {
        $tenantId = $request->attributes->get('tenant_id');

        if (! is_string($tenantId) || $tenantId === '') {
            return response('Tenant context required.', Response::HTTP_FORBIDDEN);
        }

        $tenant = Tenant::query()->find($tenantId);
        $storeName = $tenant?->name ?? 'Store';
        $siteUrl = $this->siteUrl($request, $tenant?->slug);

        $posts = BlogPost::query()
            ->where('tenant_id', $tenantId)
            ->where('status', ContentStatus::Published)
            ->orderByDesc('published_at')
            ->limit(20)
            ->get();

        $items = '';

        foreach ($posts as $post) {
            $link = htmlspecialchars($siteUrl.'/blog/'.$post->slug, ENT_XML1);
            $title = htmlspecialchars($post->title, ENT_XML1);
            $description = htmlspecialchars((string) ($post->excerpt ?? $post->title), ENT_XML1);
            $pubDate = $post->published_at?->toRfc2822String() ?? now()->toRfc2822String();
            $author = htmlspecialchars($post->author_name, ENT_XML1);

            $items .= <<<XML
    <item>
      <title>{$title}</title>
      <link>{$link}</link>
      <guid>{$link}</guid>
      <description>{$description}</description>
      <author>{$author}</author>
      <pubDate>{$pubDate}</pubDate>
    </item>

XML;
        }

        $channelTitle = htmlspecialchars("{$storeName} Blog", ENT_XML1);
        $channelLink = htmlspecialchars($siteUrl.'/blog', ENT_XML1);
        $channelDescription = htmlspecialchars("Latest posts from {$storeName}", ENT_XML1);
        $buildDate = now()->toRfc2822String();

        $xml = <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<rss version="2.0">
  <channel>
    <title>{$channelTitle}</title>
    <link>{$channelLink}</link>
    <description>{$channelDescription}</description>
    <lastBuildDate>{$buildDate}</lastBuildDate>
{$items}  </channel>
</rss>
XML;

        return response($xml, Response::HTTP_OK, [
            'Content-Type' => 'application/rss+xml; charset=UTF-8',
        ]);
    }

    private function siteUrl(Request $request, ?string $tenantSlug): string
    {
        $configured = config('cms.storefront_base_url');

        if (is_string($configured) && $configured !== '') {
            return rtrim($configured, '/');
        }

        if (is_string($tenantSlug) && $tenantSlug !== '') {
            return 'https://'.$tenantSlug.'.shops.sapphital.test';
        }

        return rtrim($request->getSchemeAndHttpHost(), '/');
    }
}
