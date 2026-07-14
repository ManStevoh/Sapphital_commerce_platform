<?php

declare(strict_types=1);

namespace Platform\Messaging\Services;

final class WebhookSigner
{
    public function sign(string $payload, string $secret, ?int $timestamp = null): string
    {
        $timestamp ??= time();
        $signed = $timestamp.'.'.$payload;
        $signature = hash_hmac('sha256', $signed, $secret);

        return 't='.$timestamp.',v1='.$signature;
    }

    public function verify(string $payload, string $header, string $secret, int $tolerance = 300): bool
    {
        $parts = [];

        foreach (explode(',', $header) as $element) {
            if (! str_contains($element, '=')) {
                continue;
            }

            [$key, $value] = explode('=', $element, 2);
            $parts[trim($key)] = trim($value);
        }

        $timestamp = (int) ($parts['t'] ?? 0);
        $signature = $parts['v1'] ?? '';

        if ($timestamp <= 0 || $signature === '') {
            return false;
        }

        if (abs(time() - $timestamp) > $tolerance) {
            return false;
        }

        $expected = hash_hmac('sha256', $timestamp.'.'.$payload, $secret);

        return hash_equals($expected, $signature);
    }
}
