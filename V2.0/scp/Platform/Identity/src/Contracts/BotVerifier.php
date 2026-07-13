<?php

declare(strict_types=1);

namespace Platform\Identity\Contracts;

interface BotVerifier
{
    public function verify(string $token, ?string $remoteIp = null): bool;
}
