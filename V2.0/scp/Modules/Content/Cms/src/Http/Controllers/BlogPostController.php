<?php

declare(strict_types=1);

namespace Modules\Content\Cms\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Modules\Content\Cms\Enums\ContentStatus;
use Modules\Content\Cms\Models\BlogPost;
use Modules\Content\Cms\Services\ContentScheduleNormalizer;
use Modules\Content\Cms\Services\ContentVersionService;
use Modules\Content\Cms\Services\SectionTreeValidator;
use Symfony\Component\HttpFoundation\Response;

final class BlogPostController
{
    public function __construct(
        private readonly ContentVersionService $versions,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $tenantId = $this->tenantId($request);

        if ($tenantId === null) {
            return $this->missingTenantResponse();
        }

        $posts = BlogPost::query()
            ->where('tenant_id', $tenantId)
            ->orderByDesc('published_at')
            ->orderByDesc('created_at')
            ->limit(100)
            ->get();

        return response()->json(['data' => $posts]);
    }

    public function indexPublished(Request $request): JsonResponse
    {
        $tenantId = $this->tenantId($request);

        if ($tenantId === null) {
            return $this->missingTenantResponse();
        }

        $limit = min(max((int) $request->integer('limit', 10), 1), 50);
        $cursor = $this->decodeCursor($request->query('cursor'));

        $query = BlogPost::query()
            ->where('tenant_id', $tenantId)
            ->where('status', ContentStatus::Published)
            ->orderByDesc('published_at')
            ->orderByDesc('id');

        if ($cursor !== null) {
            $query->where(function ($query) use ($cursor): void {
                $query->where('published_at', '<', $cursor['published_at'])
                    ->orWhere(function ($query) use ($cursor): void {
                        $query->where('published_at', $cursor['published_at'])
                            ->where('id', '<', $cursor['id']);
                    });
            });
        }

        $posts = $query
            ->limit($limit + 1)
            ->get([
                'id',
                'title',
                'slug',
                'excerpt',
                'author_name',
                'tags',
                'featured_image_url',
                'published_at',
            ]);

        $hasMore = $posts->count() > $limit;
        $visiblePosts = $posts->take($limit)->values();
        $lastPost = $visiblePosts->last();

        return response()->json([
            'data' => $visiblePosts,
            'meta' => [
                'limit' => $limit,
                'next_cursor' => $hasMore && $lastPost !== null
                    ? $this->encodeCursor((string) $lastPost->published_at, $lastPost->id)
                    : null,
            ],
        ]);
    }

    public function showPublished(Request $request, string $slug): JsonResponse
    {
        $tenantId = $this->tenantId($request);

        if ($tenantId === null) {
            return $this->missingTenantResponse();
        }

        $post = BlogPost::query()
            ->where('tenant_id', $tenantId)
            ->where('slug', $slug)
            ->where('status', ContentStatus::Published)
            ->first();

        if ($post === null) {
            return response()->json(['message' => 'Post not found.'], Response::HTTP_NOT_FOUND);
        }

        return response()->json(['data' => $post]);
    }

    public function related(Request $request, string $id): JsonResponse
    {
        $tenantId = $this->tenantId($request);

        if ($tenantId === null) {
            return $this->missingTenantResponse();
        }

        $post = BlogPost::query()
            ->where('tenant_id', $tenantId)
            ->where('id', $id)
            ->where('status', ContentStatus::Published)
            ->first();

        if ($post === null) {
            return response()->json(['message' => 'Post not found.'], Response::HTTP_NOT_FOUND);
        }

        $tags = array_values(array_filter($post->tags ?? [], 'is_string'));
        $limit = min(max((int) $request->integer('limit', 3), 1), 6);

        $query = BlogPost::query()
            ->where('tenant_id', $tenantId)
            ->where('status', ContentStatus::Published)
            ->where('id', '!=', $post->id);

        if ($tags !== []) {
            $query->where(function ($query) use ($tags): void {
                foreach ($tags as $tag) {
                    $query->orWhereJsonContains('tags', $tag);
                }
            });
        }

        $posts = $query
            ->orderByDesc('published_at')
            ->orderByDesc('id')
            ->limit($limit)
            ->get([
                'id',
                'title',
                'slug',
                'excerpt',
                'author_name',
                'tags',
                'featured_image_url',
                'published_at',
            ]);

        return response()->json(['data' => $posts]);
    }

    public function store(Request $request): JsonResponse
    {
        $tenantId = $this->tenantId($request);

        if ($tenantId === null) {
            return $this->missingTenantResponse();
        }

        $validated = $this->validatePayload($request, $tenantId);

        $post = BlogPost::query()->create([
            'tenant_id' => $tenantId,
            'title' => $validated['title'],
            'slug' => $validated['slug'],
            'excerpt' => $validated['excerpt'] ?? null,
            'body_json' => $validated['body_json'] ?? null,
            'author_name' => $validated['author_name'],
            'tags' => $validated['tags'] ?? [],
            'featured_image_url' => $validated['featured_image_url'] ?? null,
            'seo_title' => $validated['seo_title'] ?? null,
            'seo_description' => $validated['seo_description'] ?? null,
            'seo_og_image_url' => $validated['seo_og_image_url'] ?? null,
            'seo_canonical_url' => $validated['seo_canonical_url'] ?? null,
            'status' => $validated['status'],
            'published_at' => $validated['published_at'] ?? null,
            'scheduled_publish_at' => $validated['scheduled_publish_at'] ?? null,
            'scheduled_unpublish_at' => $validated['scheduled_unpublish_at'] ?? null,
        ]);

        return response()->json(['data' => $post], Response::HTTP_CREATED);
    }

    public function update(Request $request, string $id): JsonResponse
    {
        $tenantId = $this->tenantId($request);

        if ($tenantId === null) {
            return $this->missingTenantResponse();
        }

        $post = BlogPost::query()
            ->where('tenant_id', $tenantId)
            ->where('id', $id)
            ->first();

        if ($post === null) {
            return response()->json(['message' => 'Post not found.'], Response::HTTP_NOT_FOUND);
        }

        $validated = $this->validatePayload($request, $tenantId, $post->id);

        $this->versions->snapshotBlogPost($post);

        $post->update($validated);

        return response()->json(['data' => $post->fresh()]);
    }

    public function destroy(Request $request, string $id): JsonResponse
    {
        $tenantId = $this->tenantId($request);

        if ($tenantId === null) {
            return $this->missingTenantResponse();
        }

        $post = BlogPost::query()
            ->where('tenant_id', $tenantId)
            ->where('id', $id)
            ->first();

        if ($post === null) {
            return response()->json(['message' => 'Post not found.'], Response::HTTP_NOT_FOUND);
        }

        $post->delete();

        return response()->json(null, Response::HTTP_NO_CONTENT);
    }

    /**
     * @return array<string, mixed>
     */
    private function validatePayload(Request $request, string $tenantId, ?string $ignoreId = null): array
    {
        $slugRule = Rule::unique('cms_blog_posts', 'slug')
            ->where('tenant_id', $tenantId);

        if ($ignoreId !== null) {
            $slugRule = $slugRule->ignore($ignoreId);
        }

        $validated = $request->validate([
            'title' => [$ignoreId === null ? 'required' : 'sometimes', 'string', 'max:255'],
            'slug' => ['sometimes', 'string', 'max:255', $slugRule],
            'excerpt' => ['nullable', 'string', 'max:2000'],
            'body_json' => ['nullable', 'array'],
            'author_name' => [$ignoreId === null ? 'required' : 'sometimes', 'string', 'max:255'],
            'tags' => ['nullable', 'array'],
            'tags.*' => ['string', 'max:64'],
            'featured_image_url' => ['nullable', 'url', 'max:2048'],
            'seo_title' => ['nullable', 'string', 'max:255'],
            'seo_description' => ['nullable', 'string', 'max:512'],
            'seo_og_image_url' => ['nullable', 'url', 'max:2048'],
            'seo_canonical_url' => ['nullable', 'url', 'max:2048'],
            'status' => ['sometimes', Rule::enum(ContentStatus::class)],
            'published_at' => ['nullable', 'date'],
            'scheduled_publish_at' => ['nullable', 'date'],
            'scheduled_unpublish_at' => ['nullable', 'date', 'after:now'],
        ]);

        if ($ignoreId === null) {
            if (! isset($validated['slug']) && isset($validated['title'])) {
                $validated['slug'] = Str::slug($validated['title']);
            }
            $validated['slug'] = $validated['slug'] ?? Str::slug($validated['title']);
            $validated['status'] = $validated['status'] ?? ContentStatus::Draft->value;
        }

        if (array_key_exists('body_json', $validated)) {
            app(SectionTreeValidator::class)->validate($validated['body_json']);
        }

        return app(ContentScheduleNormalizer::class)->normalize($validated);
    }

    private function tenantId(Request $request): ?string
    {
        $tenantId = $request->attributes->get('tenant_id');

        return is_string($tenantId) && $tenantId !== '' ? $tenantId : null;
    }

    /**
     * @return array{published_at: string, id: string}|null
     */
    private function decodeCursor(mixed $cursor): ?array
    {
        if (! is_string($cursor) || $cursor === '') {
            return null;
        }

        $decoded = json_decode((string) base64_decode($cursor, true), true);

        if (
            ! is_array($decoded)
            || ! isset($decoded['published_at'], $decoded['id'])
            || ! is_string($decoded['published_at'])
            || ! is_string($decoded['id'])
        ) {
            abort(Response::HTTP_UNPROCESSABLE_ENTITY, 'Invalid cursor.');
        }

        return [
            'published_at' => Carbon::parse($decoded['published_at'])->toDateTimeString(),
            'id' => $decoded['id'],
        ];
    }

    private function encodeCursor(string $publishedAt, string $id): string
    {
        return base64_encode((string) json_encode([
            'published_at' => Carbon::parse($publishedAt)->toDateTimeString(),
            'id' => $id,
        ], JSON_THROW_ON_ERROR));
    }

    private function missingTenantResponse(): JsonResponse
    {
        return response()->json([
            'message' => 'Tenant context required.',
        ], Response::HTTP_FORBIDDEN);
    }
}
