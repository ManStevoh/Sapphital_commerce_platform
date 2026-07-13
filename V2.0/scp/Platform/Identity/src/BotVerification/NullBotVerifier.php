<?php

declare(strict_types=1);

namespace Platform\Identity\BotVerification;

use Platform\Identity\Contracts\BotVerifier;

final class NullBotVerifier implements BotVerifier
{
    public function verify(string $token, ?string $remoteIp = null): bool
    {
        return true;
    }
}
