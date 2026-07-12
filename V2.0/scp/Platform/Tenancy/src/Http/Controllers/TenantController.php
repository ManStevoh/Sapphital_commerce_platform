<?php

declare(strict_types=1);

namespace Platform\Tenancy\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Platform\Identity\Models\PlatformAdmin;
use Platform\Tenancy\Models\Tenant;

final class TenantController
{
    private const int MAX_PER_PAGE = 50;

    public function showBySlug(string $slug): JsonResponse
    {
        $tenant = Tenant::query()
            ->where('slug', strtolower($slug))
            ->first([
                'id',
                'slug',
                'name',
                'status',
            ]);

        if ($tenant === null) {
            return response()->json([
                'message' => 'Tenant not found.',
            ], 404);
        }

        return response()->json([
            'id' => $tenant->id,
            'slug' => $tenant->slug,
            'name' => $tenant->name,
            'status' => $tenant->status,
        ]);
    }

    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        if (! $user instanceof PlatformAdmin) {
            return response()->json([
                'message' => 'Forbidden.',
            ], 403);
        }

        $perPage = min(
            max((int) $request->query('per_page', 15), 1),
            self::MAX_PER_PAGE,
        );

        $paginator = Tenant::query()
            ->orderByDesc('created_at')
            ->paginate($perPage, [
                'id',
                'slug',
                'name',
                'status',
                'country',
                'created_at',
            ]);

        $data = $paginator->getCollection()
            ->map(static fn (Tenant $tenant): array => [
                'id' => $tenant->id,
                'slug' => $tenant->slug,
                'name' => $tenant->name,
                'status' => $tenant->status,
                'country' => $tenant->country,
                'created_at' => $tenant->created_at?->toIso8601String(),
            ])
            ->values()
            ->all();

        return response()->json([
            'data' => $data,
            'meta' => [
                'total' => $paginator->total(),
            ],
        ]);
    }

    public function updateStatus(Request $request, string $id): JsonResponse
    {
        $user = $request->user();

        if (! $user instanceof PlatformAdmin) {
            return response()->json([
                'message' => 'Forbidden.',
            ], 403);
        }

        $validated = $request->validate([
            'status' => ['required', 'string', 'in:active,suspended'],
        ]);

        $tenant = Tenant::query()->find($id);

        if ($tenant === null) {
            return response()->json([
                'message' => 'Tenant not found.',
            ], 404);
        }

        $tenant->update([
            'status' => $validated['status'],
        ]);

        return response()->json([
            'data' => [
                'id' => $tenant->id,
                'slug' => $tenant->slug,
                'name' => $tenant->name,
                'status' => $tenant->status,
                'country' => $tenant->country,
                'created_at' => $tenant->created_at?->toIso8601String(),
            ],
        ]);
    }
}
