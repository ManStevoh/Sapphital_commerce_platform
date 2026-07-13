<?php

declare(strict_types=1);

namespace Platform\Identity\BotVerification;

use Illuminate\Support\Facades\Http;
use Platform\Identity\Contracts\BotVerifier;

final class TurnstileBotVerifier implements BotVerifier
{
    public function verify(string $token, ?string $remoteIp = null): bool
    {
        $secretKey = config('turnstile.secret_key');

        if (! is_string($secretKey) || $secretKey === '') {
            return false;
        }

        try {
            $response = Http::timeout(2)
                ->asForm()
                ->post((string) config('turnstile.verify_url'), array_filter([
                    'secret' => $secretKey,
                    'response' => $token,
                    'remoteip' => $remoteIp,
                ]));

            if (! $response->ok()) {
                return false;
            }

            return (bool) ($response->json('success') ?? false);
        } catch (\Throwable) {
            return false;
        }
    }
}
