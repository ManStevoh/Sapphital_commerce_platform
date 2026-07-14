<?php

declare(strict_types=1);

namespace Platform\Tenancy\Contracts;

interface DomainDnsVerifier
{
    /**
     * @return array{txt_ok: bool, cname_ok: bool, details: list<string>}
     */
    public function check(string $domain, string $expectedTxtToken, string $expectedCnameTarget): array;
}
