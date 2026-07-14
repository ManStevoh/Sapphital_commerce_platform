<?php

declare(strict_types=1);

namespace Platform\Tenancy\Services;

use Platform\Tenancy\Contracts\DomainDnsVerifier;

/**
 * Test/local verifier. Configure matches via config('domains.fake_dns') or always-pass keys.
 */
final class FakeDomainDnsVerifier implements DomainDnsVerifier
{
    public function check(string $domain, string $expectedTxtToken, string $expectedCnameTarget): array
    {
        $map = config('domains.fake_dns', []);
        $entry = is_array($map) ? ($map[strtolower($domain)] ?? null) : null;

        if (! is_array($entry)) {
            return [
                'txt_ok' => false,
                'cname_ok' => false,
                'details' => ['No fake DNS configured for '.$domain],
            ];
        }

        $txtOk = ($entry['txt'] ?? null) === $expectedTxtToken;
        $cnameOk = strtolower((string) ($entry['cname'] ?? '')) === strtolower($expectedCnameTarget);

        return [
            'txt_ok' => $txtOk,
            'cname_ok' => $cnameOk,
            'details' => [
                $txtOk ? 'TXT verified (fake)' : 'TXT failed (fake)',
                $cnameOk ? 'CNAME verified (fake)' : 'CNAME failed (fake)',
            ],
        ];
    }
}
