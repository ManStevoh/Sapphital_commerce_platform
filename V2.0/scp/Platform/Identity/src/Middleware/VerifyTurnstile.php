<?php

declare(strict_types=1);

namespace Platform\Identity\Middleware;

use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Platform\Identity\Contracts\BotVerifier;
use Symfony\Component\HttpFoundation\Response;

final class VerifyTurnstile
{
    public function __construct(
        private readonly BotVerifier $botVerifier,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        if (! config('turnstile.enabled')) {
            return $next($request);
        }

        $token = $request->input('cf-turnstile-response', $request->header('CF-Turnstile-Response'));

        if (! is_string($token) || $token === '') {
            return $this->reject('Turnstile verification token is required.');
        }

        if (! $this->botVerifier->verify($token, $request->ip())) {
            return $this->reject('Turnstile verification failed.');
        }

        return $next($request);
    }

    private function reject(string $message): JsonResponse
    {
        return response()->json([
            'message' => $message,
            'errors' => [
                'cf-turnstile-response' => [$message],
            ],
        ], Response::HTTP_UNPROCESSABLE_ENTITY);
    }
}
