<?php

declare(strict_types=1);

namespace Platform\Tenancy\Middleware;

use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

final class EnsureIdempotency
{
    private const TTL_SECONDS = 86_400;

    public function handle(Request $request, Closure $next): Response
    {
        $key = $request->header('Idempotency-Key');

        if (! is_string($key) || $key === '') {
            return $this->validationError('Idempotency-Key header is required.');
        }

        if (strlen($key) > 64 || ! Str::isUuid($key)) {
            return $this->validationError('Idempotency-Key must be a valid UUID.');
        }

        $tenantId = (string) $request->attributes->get('tenant_id', '');
        $bodyHash = hash('sha256', $request->getContent());
        $cacheKey = sprintf('idempotency:%s:%s:%s', $tenantId, $request->path(), $key);

        /** @var array{request_hash: string, status: int, body: mixed}|null $cached */
        $cached = Cache::get($cacheKey);

        if (is_array($cached)) {
            if (($cached['request_hash'] ?? '') !== $bodyHash) {
                return response()->json([
                    'message' => 'Idempotency key reused with different request body.',
                    'errors' => [
                        'idempotency_key' => ['Idempotency key reused with different request body.'],
                    ],
                ], Response::HTTP_UNPROCESSABLE_ENTITY);
            }

            return response()->json($cached['body'], (int) ($cached['status'] ?? 200));
        }

        $response = $next($request);

        if ($response->getStatusCode() >= 200 && $response->getStatusCode() < 300) {
            $decoded = json_decode($response->getContent(), true);

            Cache::put($cacheKey, [
                'request_hash' => $bodyHash,
                'status' => $response->getStatusCode(),
                'body' => $decoded,
            ], self::TTL_SECONDS);
        }

        return $response;
    }

    private function validationError(string $message): JsonResponse
    {
        return response()->json([
            'message' => $message,
            'errors' => [
                'idempotency_key' => [$message],
            ],
        ], Response::HTTP_UNPROCESSABLE_ENTITY);
    }
}
