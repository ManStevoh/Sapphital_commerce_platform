<?php

declare(strict_types=1);

namespace Platform\Messaging\Services;

use InvalidArgumentException;

final class WebhookUrlGuard
{
    public function assertSafe(string $url): void
    {
        $parts = parse_url($url);

        if ($parts === false || ! isset($parts['scheme'], $parts['host'])) {
            throw new InvalidArgumentException('Webhook URL is invalid.');
        }

        $scheme = strtolower((string) $parts['scheme']);

        if (! in_array($scheme, ['https', 'http'], true)) {
            throw new InvalidArgumentException('Webhook URL must use http or https.');
        }

        if (app()->environment('production') && $scheme !== 'https') {
            throw new InvalidArgumentException('Webhook URL must use https in production.');
        }

        $host = strtolower((string) $parts['host']);

        if ($host === 'localhost' || str_ends_with($host, '.localhost') || $host === '0.0.0.0') {
            throw new InvalidArgumentException('Webhook URL host is not allowed.');
        }

        if (filter_var($host, FILTER_VALIDATE_IP) && $this->isPrivateIp($host)) {
            throw new InvalidArgumentException('Webhook URL resolves to a private or link-local address.');
        }

        // Avoid brittle DNS in unit/feature tests; still block literal private IPs above.
        if (app()->environment('testing')) {
            return;
        }

        $resolved = gethostbynamel($host);

        if (! is_array($resolved)) {
            return;
        }

        foreach ($resolved as $ip) {
            if ($this->isPrivateIp($ip)) {
                throw new InvalidArgumentException('Webhook URL resolves to a private or link-local address.');
            }
        }
    }

    private function isPrivateIp(string $ip): bool
    {
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            return ! filter_var(
                $ip,
                FILTER_VALIDATE_IP,
                FILTER_FLAG_IPV4 | FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE,
            );
        }

        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
            return ! filter_var(
                $ip,
                FILTER_VALIDATE_IP,
                FILTER_FLAG_IPV6 | FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE,
            );
        }

        return true;
    }
}
