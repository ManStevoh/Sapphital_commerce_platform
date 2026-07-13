<?php

declare(strict_types=1);

namespace Modules\Content\Cms\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Content\Cms\Models\BlogPost;
use Modules\Content\Cms\Models\ContentVersion;
use Modules\Content\Cms\Models\Page;
use Modules\Content\Cms\Services\ContentVersionService;
use Symfony\Component\HttpFoundation\Response;

final class ContentVersionController
{
    public function __construct(
        private readonly ContentVersionService $versions,
    ) {}

    public function indexPages(Request $request, string $id): JsonResponse
    {
        return $this->index($request, ContentVersion::ENTITY_PAGE, $id, Page::class);
    }

    public function restorePage(Request $request, string $id, string $versionId): JsonResponse
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

        $version = $this->versions->find($tenantId, ContentVersion::ENTITY_PAGE, $id, $versionId);

        if ($version === null) {
            return response()->json(['message' => 'Version not found.'], Response::HTTP_NOT_FOUND);
        }

        $restored = $this->versions->restorePage($page, $version);

        return response()->json(['data' => $restored]);
    }

    public function indexBlogPosts(Request $request, string $id): JsonResponse
    {
        return $this->index($request, ContentVersion::ENTITY_BLOG_POST, $id, BlogPost::class);
    }

    public function restoreBlogPost(Request $request, string $id, string $versionId): JsonResponse
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

        $version = $this->versions->find($tenantId, ContentVersion::ENTITY_BLOG_POST, $id, $versionId);

        if ($version === null) {
            return response()->json(['message' => 'Version not found.'], Response::HTTP_NOT_FOUND);
        }

        $restored = $this->versions->restoreBlogPost($post, $version);

        return response()->json(['data' => $restored]);
    }

    /**
     * @param  class-string  $modelClass
     */
    private function index(Request $request, string $entityType, string $id, string $modelClass): JsonResponse
    {
        $tenantId = $this->tenantId($request);

        if ($tenantId === null) {
            return $this->missingTenantResponse();
        }

        $exists = $modelClass::query()
            ->where('tenant_id', $tenantId)
            ->where('id', $id)
            ->exists();

        if (! $exists) {
            return response()->json(['message' => 'Content not found.'], Response::HTTP_NOT_FOUND);
        }

        $versions = $this->versions->list($tenantId, $entityType, $id)->map(static fn (ContentVersion $version): array => [
            'id' => $version->id,
            'version_number' => $version->version_number,
            'label' => $version->label,
            'created_at' => $version->created_at?->toIso8601String(),
            'snapshot_json' => $version->snapshot_json,
        ]);

        return response()->json(['data' => $versions]);
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
