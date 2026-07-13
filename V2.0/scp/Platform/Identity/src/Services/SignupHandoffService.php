<?php

declare(strict_types=1);

namespace Platform\Identity\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

final class SignupHandoffService
{
    private const CACHE_PREFIX = 'signup_handoff:';

    private const TTL_MINUTES = 30;

    public function create(string $merchantUserId, string $tenantId): string
    {
        $token = Str::random(64);

        Cache::put(self::CACHE_PREFIX.$token, [
            'merchant_user_id' => $merchantUserId,
            'tenant_id' => $tenantId,
        ], now()->addMinutes(self::TTL_MINUTES));

        return $token;
    }

    /**
     * @return array{merchant_user_id: string, tenant_id: string}
     */
    public function consume(string $token): array
    {
        $payload = Cache::pull(self::CACHE_PREFIX.$token);

        if (! is_array($payload)) {
            throw ValidationException::withMessages([
                'handoff_token' => ['Handoff token is invalid or expired.'],
            ]);
        }

        $merchantUserId = $payload['merchant_user_id'] ?? null;
        $tenantId = $payload['tenant_id'] ?? null;

        if (! is_string($merchantUserId) || $merchantUserId === '' || ! is_string($tenantId) || $tenantId === '') {
            throw ValidationException::withMessages([
                'handoff_token' => ['Handoff token is invalid or expired.'],
            ]);
        }

        return [
            'merchant_user_id' => $merchantUserId,
            'tenant_id' => $tenantId,
        ];
    }
}
