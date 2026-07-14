<?php

declare(strict_types=1);

namespace Platform\Messaging\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Platform\Messaging\Models\WebhookEndpoint;
use Platform\Messaging\Services\WebhookUrlGuard;
use Symfony\Component\HttpFoundation\Response;

final class WebhookEndpointController
{
    public function __construct(
        private readonly WebhookUrlGuard $urlGuard,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $tenantId = $this->tenantId($request);

        if ($tenantId === null) {
            return $this->missingTenantResponse();
        }

        $endpoints = WebhookEndpoint::query()
            ->where('tenant_id', $tenantId)
            ->orderByDesc('created_at')
            ->limit(50)
            ->get()
            ->map(fn (WebhookEndpoint $endpoint): array => $this->serialize($endpoint))
            ->values()
            ->all();

        return response()->json([
            'data' => $endpoints,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $tenantId = $this->tenantId($request);

        if ($tenantId === null) {
            return $this->missingTenantResponse();
        }

        $validated = $request->validate([
            'url' => ['required', 'string', 'url', 'max:2048'],
            'topics' => ['required', 'array', 'min:1', 'max:50'],
            'topics.*' => ['required', 'string', 'max:128'],
            'description' => ['nullable', 'string', 'max:255'],
        ]);

        try {
            $this->urlGuard->assertSafe($validated['url']);
        } catch (InvalidArgumentException $exception) {
            return response()->json([
                'message' => $exception->getMessage(),
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $plainSecret = 'whsec_'.Str::lower(Str::random(32));

        $endpoint = WebhookEndpoint::query()->create([
            'tenant_id' => $tenantId,
            'url' => $validated['url'],
            'topics' => array_values($validated['topics']),
            'description' => $validated['description'] ?? null,
            'status' => WebhookEndpoint::STATUS_ACTIVE,
            'secret' => $plainSecret,
        ]);

        return response()->json([
            'data' => array_merge($this->serialize($endpoint), [
                'secret' => $plainSecret,
            ]),
        ], Response::HTTP_CREATED);
    }

    public function destroy(Request $request, string $id): JsonResponse
    {
        $tenantId = $this->tenantId($request);

        if ($tenantId === null) {
            return $this->missingTenantResponse();
        }

        $endpoint = WebhookEndpoint::query()
            ->where('tenant_id', $tenantId)
            ->whereKey($id)
            ->first();

        if ($endpoint === null) {
            return response()->json([
                'message' => 'Webhook endpoint not found.',
            ], Response::HTTP_NOT_FOUND);
        }

        $endpoint->delete();

        return response()->json([
            'message' => 'Webhook endpoint deleted.',
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function serialize(WebhookEndpoint $endpoint): array
    {
        return [
            'id' => $endpoint->id,
            'url' => $endpoint->url,
            'topics' => $endpoint->topics,
            'description' => $endpoint->description,
            'status' => $endpoint->status,
            'created_at' => $endpoint->created_at?->toIso8601String(),
            'updated_at' => $endpoint->updated_at?->toIso8601String(),
        ];
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
