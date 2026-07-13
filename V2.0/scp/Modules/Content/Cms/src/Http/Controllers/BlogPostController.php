<?php

declare(strict_types=1);

namespace Modules\Content\Cms\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Modules\Content\Cms\Enums\ContentStatus;
use Modules\Content\Cms\Models\BlogPost;
use Symfony\Component\HttpFoundation\Response;

final class BlogPostController
{
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
            ->get();

        return response()->json(['data' => $posts]);
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
            'status' => $validated['status'],
            'published_at' => $validated['published_at'] ?? null,
        ]);

        return response()->json(['data' => $post], Response::HTTP_CREATED);
    }

    /**
     * @return array<string, mixed>
     */
    private function validatePayload(Request $request, string $tenantId): array
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'slug' => [
                'nullable',
                'string',
                'max:255',
                Rule::unique('cms_blog_posts', 'slug')->where('tenant_id', $tenantId),
            ],
            'excerpt' => ['nullable', 'string', 'max:2000'],
            'body_json' => ['nullable', 'array'],
            'author_name' => ['required', 'string', 'max:255'],
            'tags' => ['nullable', 'array'],
            'tags.*' => ['string', 'max:64'],
            'featured_image_url' => ['nullable', 'url', 'max:2048'],
            'seo_title' => ['nullable', 'string', 'max:255'],
            'seo_description' => ['nullable', 'string', 'max:512'],
            'status' => ['sometimes', Rule::enum(ContentStatus::class)],
            'published_at' => ['nullable', 'date'],
        ]);

        $validated['slug'] = $validated['slug'] ?? Str::slug($validated['title']);
        $validated['status'] = $validated['status'] ?? ContentStatus::Draft->value;

        return $validated;
    }

    private function tenantId(Request $request): ?string
    {
        $tenantId = $request->attributes->get('tenant_id');

        return is_string($tenantId) && $tenantId !== '' ? $tenantId : null;
    }

    private function missingTenantResponse(): JsonResponse
    {
        return response()->json([
            'message' => 'Tenant context required.',
        ], Response::HTTP_FORBIDDEN);
    }
}
