<?php

declare(strict_types=1);

namespace Platform\Tenancy\Services;

use Platform\Tenancy\Contracts\DomainDnsVerifier;

/**
 * Production DNS checks via dns_get_record. Fail-closed when records missing.
 */
final class PhpDomainDnsVerifier implements DomainDnsVerifier
{
    public function check(string $domain, string $expectedTxtToken, string $expectedCnameTarget): array
    {
        $details = [];
        $domain = strtolower(trim($domain));
        $txtHost = (string) config('domains.txt_host_prefix', '_sapphital-verify').'.'.$domain;

        $txtOk = false;
        $txtRecords = @dns_get_record($txtHost, DNS_TXT) ?: [];
        foreach ($txtRecords as $record) {
            $txt = (string) ($record['txt'] ?? '');
            if (hash_equals($expectedTxtToken, $txt)) {
                $txtOk = true;
                break;
            }
        }
        $details[] = $txtOk ? 'TXT verified' : 'TXT missing or mismatched on '.$txtHost;

        $cnameOk = false;
        $cnameRecords = @dns_get_record($domain, DNS_CNAME) ?: [];
        $expected = strtolower(rtrim($expectedCnameTarget, '.'));
        foreach ($cnameRecords as $record) {
            $target = strtolower(rtrim((string) ($record['target'] ?? ''), '.'));
            if ($target === $expected) {
                $cnameOk = true;
                break;
            }
        }
        $details[] = $cnameOk ? 'CNAME verified' : 'CNAME missing or mismatched (expected '.$expected.')';

        return [
            'txt_ok' => $txtOk,
            'cname_ok' => $cnameOk,
            'details' => $details,
        ];
    }
}
