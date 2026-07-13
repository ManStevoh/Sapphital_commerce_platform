<?php

declare(strict_types=1);

namespace Platform\Ai\Services;

final class PiiScrubber
{
    public function scrub(string $text): string
    {
        $scrubbed = preg_replace(
            '/[a-zA-Z0-9._%+\-]+@[a-zA-Z0-9.\-]+\.[a-zA-Z]{2,}/',
            '[redacted-email]',
            $text,
        ) ?? $text;

        $scrubbed = preg_replace(
            '/\b(?:\+?234|0)(?:\d[\s\-]?){7,11}\d\b/',
            '[redacted-phone]',
            $scrubbed,
        ) ?? $scrubbed;

        return $scrubbed;
    }
}
