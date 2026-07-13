<?php

declare(strict_types=1);

namespace Modules\Content\Cms\Services;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Modules\Content\Cms\Models\BlogPost;
use Modules\Content\Cms\Models\ContentVersion;
use Modules\Content\Cms\Models\Page;

final class ContentVersionService
{
    public const MAX_VERSIONS = 10;

    public function snapshotPage(Page $page, ?string $label = null): ContentVersion
    {
        return $this->snapshot(
            ContentVersion::ENTITY_PAGE,
            $page,
            $this->pageSnapshot($page),
            $label,
        );
    }

    public function snapshotBlogPost(BlogPost $post, ?string $label = null): ContentVersion
    {
        return $this->snapshot(
            ContentVersion::ENTITY_BLOG_POST,
            $post,
            $this->blogPostSnapshot($post),
            $label,
        );
    }

    /**
     * @return Collection<int, ContentVersion>
     */
    public function list(string $tenantId, string $entityType, string $entityId): Collection
    {
        return ContentVersion::query()
            ->where('tenant_id', $tenantId)
            ->where('entity_type', $entityType)
            ->where('entity_id', $entityId)
            ->orderByDesc('version_number')
            ->limit(self::MAX_VERSIONS)
            ->get();
    }

    public function find(string $tenantId, string $entityType, string $entityId, string $versionId): ?ContentVersion
    {
        return ContentVersion::query()
            ->where('tenant_id', $tenantId)
            ->where('entity_type', $entityType)
            ->where('entity_id', $entityId)
            ->where('id', $versionId)
            ->first();
    }

    public function restorePage(Page $page, ContentVersion $version): Page
    {
        $this->snapshotPage($page, 'Before restore');

        $snapshot = $version->snapshot_json ?? [];
        $page->update($this->pageAttributesFromSnapshot($snapshot));

        return $page->fresh() ?? $page;
    }

    public function restoreBlogPost(BlogPost $post, ContentVersion $version): BlogPost
    {
        $this->snapshotBlogPost($post, 'Before restore');

        $snapshot = $version->snapshot_json ?? [];
        $post->update($this->blogPostAttributesFromSnapshot($snapshot));

        return $post->fresh() ?? $post;
    }

    /**
     * @param  array<string, mixed>  $snapshot
     */
    private function snapshot(string $entityType, Model $entity, array $snapshot, ?string $label): ContentVersion
    {
        $tenantId = (string) $entity->getAttribute('tenant_id');
        $entityId = (string) $entity->getAttribute('id');

        $nextVersion = ((int) ContentVersion::query()
            ->where('tenant_id', $tenantId)
            ->where('entity_type', $entityType)
            ->where('entity_id', $entityId)
            ->max('version_number')) + 1;

        $version = ContentVersion::query()->create([
            'tenant_id' => $tenantId,
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'version_number' => $nextVersion,
            'snapshot_json' => $snapshot,
            'label' => $label,
        ]);

        $this->prune($tenantId, $entityType, $entityId);

        return $version;
    }

    private function prune(string $tenantId, string $entityType, string $entityId): void
    {
        $keepIds = ContentVersion::query()
            ->where('tenant_id', $tenantId)
            ->where('entity_type', $entityType)
            ->where('entity_id', $entityId)
            ->orderByDesc('version_number')
            ->limit(self::MAX_VERSIONS)
            ->pluck('id');

        ContentVersion::query()
            ->where('tenant_id', $tenantId)
            ->where('entity_type', $entityType)
            ->where('entity_id', $entityId)
            ->whereNotIn('id', $keepIds)
            ->delete();
    }

    /**
     * @return array<string, mixed>
     */
    private function pageSnapshot(Page $page): array
    {
        return [
            'title' => $page->title,
            'slug' => $page->slug,
            'content_type' => $page->content_type?->value ?? $page->content_type,
            'body_json' => $page->body_json,
            'seo_title' => $page->seo_title,
            'seo_description' => $page->seo_description,
            'seo_og_image_url' => $page->seo_og_image_url,
            'seo_canonical_url' => $page->seo_canonical_url,
            'status' => $page->status?->value ?? $page->status,
            'published_at' => $page->published_at?->toIso8601String(),
            'scheduled_publish_at' => $page->scheduled_publish_at?->toIso8601String(),
            'scheduled_unpublish_at' => $page->scheduled_unpublish_at?->toIso8601String(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function blogPostSnapshot(BlogPost $post): array
    {
        return [
            'title' => $post->title,
            'slug' => $post->slug,
            'excerpt' => $post->excerpt,
            'body_json' => $post->body_json,
            'author_name' => $post->author_name,
            'tags' => $post->tags,
            'featured_image_url' => $post->featured_image_url,
            'seo_title' => $post->seo_title,
            'seo_description' => $post->seo_description,
            'seo_og_image_url' => $post->seo_og_image_url,
            'seo_canonical_url' => $post->seo_canonical_url,
            'status' => $post->status?->value ?? $post->status,
            'published_at' => $post->published_at?->toIso8601String(),
            'scheduled_publish_at' => $post->scheduled_publish_at?->toIso8601String(),
            'scheduled_unpublish_at' => $post->scheduled_unpublish_at?->toIso8601String(),
        ];
    }

    /**
     * @param  array<string, mixed>  $snapshot
     * @return array<string, mixed>
     */
    private function pageAttributesFromSnapshot(array $snapshot): array
    {
        return array_intersect_key($snapshot, array_flip([
            'title',
            'slug',
            'content_type',
            'body_json',
            'seo_title',
            'seo_description',
            'seo_og_image_url',
            'seo_canonical_url',
            'status',
            'published_at',
            'scheduled_publish_at',
            'scheduled_unpublish_at',
        ]));
    }

    /**
     * @param  array<string, mixed>  $snapshot
     * @return array<string, mixed>
     */
    private function blogPostAttributesFromSnapshot(array $snapshot): array
    {
        return array_intersect_key($snapshot, array_flip([
            'title',
            'slug',
            'excerpt',
            'body_json',
            'author_name',
            'tags',
            'featured_image_url',
            'seo_title',
            'seo_description',
            'seo_og_image_url',
            'seo_canonical_url',
            'status',
            'published_at',
            'scheduled_publish_at',
            'scheduled_unpublish_at',
        ]));
    }
}
