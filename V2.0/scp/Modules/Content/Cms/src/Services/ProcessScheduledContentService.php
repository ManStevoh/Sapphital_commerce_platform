<?php

declare(strict_types=1);

namespace Modules\Content\Cms\Services;

use Illuminate\Support\Carbon;
use Modules\Content\Cms\Enums\ContentStatus;
use Modules\Content\Cms\Models\BlogPost;
use Modules\Content\Cms\Models\Page;

final class ProcessScheduledContentService
{
    public function run(?Carbon $asOf = null): int
    {
        $asOf ??= now();
        $processed = 0;

        $processed += $this->publishDuePages($asOf);
        $processed += $this->publishDueBlogPosts($asOf);
        $processed += $this->unpublishDuePages($asOf);
        $processed += $this->unpublishDueBlogPosts($asOf);

        return $processed;
    }

    private function publishDuePages(Carbon $asOf): int
    {
        $pages = Page::query()
            ->where('status', ContentStatus::Scheduled)
            ->whereNotNull('scheduled_publish_at')
            ->where('scheduled_publish_at', '<=', $asOf)
            ->limit(500)
            ->get();

        foreach ($pages as $page) {
            $page->update([
                'status' => ContentStatus::Published,
                'published_at' => $page->scheduled_publish_at ?? $asOf,
                'scheduled_publish_at' => null,
            ]);
        }

        return $pages->count();
    }

    private function publishDueBlogPosts(Carbon $asOf): int
    {
        $posts = BlogPost::query()
            ->where('status', ContentStatus::Scheduled)
            ->whereNotNull('scheduled_publish_at')
            ->where('scheduled_publish_at', '<=', $asOf)
            ->limit(500)
            ->get();

        foreach ($posts as $post) {
            $post->update([
                'status' => ContentStatus::Published,
                'published_at' => $post->scheduled_publish_at ?? $asOf,
                'scheduled_publish_at' => null,
            ]);
        }

        return $posts->count();
    }

    private function unpublishDuePages(Carbon $asOf): int
    {
        $pages = Page::query()
            ->where('status', ContentStatus::Published)
            ->whereNotNull('scheduled_unpublish_at')
            ->where('scheduled_unpublish_at', '<=', $asOf)
            ->limit(500)
            ->get();

        foreach ($pages as $page) {
            $page->update([
                'status' => ContentStatus::Draft,
                'published_at' => null,
                'scheduled_unpublish_at' => null,
            ]);
        }

        return $pages->count();
    }

    private function unpublishDueBlogPosts(Carbon $asOf): int
    {
        $posts = BlogPost::query()
            ->where('status', ContentStatus::Published)
            ->whereNotNull('scheduled_unpublish_at')
            ->where('scheduled_unpublish_at', '<=', $asOf)
            ->limit(500)
            ->get();

        foreach ($posts as $post) {
            $post->update([
                'status' => ContentStatus::Draft,
                'published_at' => null,
                'scheduled_unpublish_at' => null,
            ]);
        }

        return $posts->count();
    }
}
