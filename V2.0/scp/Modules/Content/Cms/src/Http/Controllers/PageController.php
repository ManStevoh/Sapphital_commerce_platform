<?php

declare(strict_types=1);

namespace Modules\Content\Cms\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Modules\Content\Cms\Enums\ContentStatus;
use Modules\Content\Cms\Enums\PageContentType;
use Modules\Content\Cms\Models\Page;
use Modules\Content\Cms\Services\ContentScheduleNormalizer;
use Modules\Content\Cms\Services\ContentVersionService;
use Modules\Content\Cms\Services\SectionTreeValidator;
use Symfony\Component\HttpFoundation\Response;

final class PageController
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

        $pages = Page::query()
            ->where('tenant_id', $tenantId)
            ->orderBy('title')
            ->get();

        return response()->json(['data' => $pages]);
    }

    public function indexPublished(Request $request): JsonResponse
    {
        $tenantId = $this->tenantId($request);

        if ($tenantId === null) {
            return $this->missingTenantResponse();
        }

        $pages = Page::query()
            ->where('tenant_id', $tenantId)
            ->where('status', ContentStatus::Published)
            ->orderBy('title')
            ->get([
                'id',
                'title',
                'slug',
                'published_at',
                'updated_at',
            ]);

        return response()->json(['data' => $pages]);
    }

    public function showBySlug(Request $request, string $slug): JsonResponse
    {
        $tenantId = $this->tenantId($request);

        if ($tenantId === null) {
            return $this->missingTenantResponse();
        }

        $page = Page::query()
            ->where('tenant_id', $tenantId)
            ->where('slug', $slug)
            ->where('status', ContentStatus::Published)
            ->first();

        if ($page === null) {
            return response()->json(['message' => 'Page not found.'], Response::HTTP_NOT_FOUND);
        }

        return response()->json(['data' => $page]);
    }

    public function store(Request $request): JsonResponse
    {
        $tenantId = $this->tenantId($request);

        if ($tenantId === null) {
            return $this->missingTenantResponse();
        }

        $validated = $this->validatePayload($request, $tenantId);

        $page = Page::query()->create([
            'tenant_id' => $tenantId,
            'title' => $validated['title'],
            'slug' => $validated['slug'],
            'content_type' => $validated['content_type'],
            'body_json' => $validated['body_json'] ?? null,
            'seo_title' => $validated['seo_title'] ?? null,
            'seo_description' => $validated['seo_description'] ?? null,
            'seo_og_image_url' => $validated['seo_og_image_url'] ?? null,
            'seo_canonical_url' => $validated['seo_canonical_url'] ?? null,
            'status' => $validated['status'],
            'published_at' => $validated['published_at'] ?? null,
            'scheduled_publish_at' => $validated['scheduled_publish_at'] ?? null,
            'scheduled_unpublish_at' => $validated['scheduled_unpublish_at'] ?? null,
        ]);

        return response()->json(['data' => $page], Response::HTTP_CREATED);
    }

    public function update(Request $request, string $id): JsonResponse
    {
        $tenantId = $this->tenantId($request);

        if ($tenantId === null) {
            return $this->missingTenantResponse();
        }

        $page = Page::query()
            ->where('tenant_id', $tenantId)
            ->where('id', $id)
            ->first();

        if ($page === null) {
            return response()->json(['message' => 'Page not found.'], Response::HTTP_NOT_FOUND);
        }

        $validated = $this->validatePayload($request, $tenantId, $page->id);

        $this->versions->snapshotPage($page);

        $page->update($validated);

        return response()->json(['data' => $page->fresh()]);
    }

    public function destroy(Request $request, string $id): JsonResponse
    {
        $tenantId = $this->tenantId($request);

        if ($tenantId === null) {
            return $this->missingTenantResponse();
        }

        $page = Page::query()
            ->where('tenant_id', $tenantId)
            ->where('id', $id)
            ->first();

        if ($page === null) {
            return response()->json(['message' => 'Page not found.'], Response::HTTP_NOT_FOUND);
        }

        $page->delete();

        return response()->json(null, Response::HTTP_NO_CONTENT);
    }

    /**
     * @return array<string, mixed>
     */
    private function validatePayload(Request $request, string $tenantId, ?string $ignoreId = null): array
    {
        $slugRule = Rule::unique('cms_pages', 'slug')
            ->where('tenant_id', $tenantId);

        if ($ignoreId !== null) {
            $slugRule = $slugRule->ignore($ignoreId);
        }

        $validated = $request->validate([
            'title' => [$ignoreId === null ? 'required' : 'sometimes', 'string', 'max:255'],
            'slug' => ['sometimes', 'string', 'max:255', $slugRule],
            'content_type' => ['sometimes', Rule::enum(PageContentType::class)],
            'body_json' => ['nullable', 'array'],
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
            $validated['content_type'] = $validated['content_type'] ?? PageContentType::Page->value;
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

    private function missingTenantResponse(): JsonResponse
    {
        return response()->json([
            'message' => 'Tenant context required.',
        ], Response::HTTP_FORBIDDEN);
    }
}
