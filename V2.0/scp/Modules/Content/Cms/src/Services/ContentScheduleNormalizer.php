<?php

declare(strict_types=1);

namespace Modules\Content\Cms\Services;

use Illuminate\Validation\ValidationException;
use Modules\Content\Cms\Enums\ContentStatus;

final class ContentScheduleNormalizer
{
    /**
     * @param  array<string, mixed>  $validated
     * @return array<string, mixed>
     */
    public function normalize(array $validated): array
    {
        $status = $validated['status'] ?? null;

        if ($status === ContentStatus::Scheduled->value) {
            if (! isset($validated['scheduled_publish_at']) || $validated['scheduled_publish_at'] === null) {
                throw ValidationException::withMessages([
                    'scheduled_publish_at' => ['A scheduled publish time is required when status is scheduled.'],
                ]);
            }

            $validated['published_at'] = null;
            $validated['scheduled_unpublish_at'] = null;
        }

        if ($status === ContentStatus::Published->value) {
            $validated['scheduled_publish_at'] = null;
        }

        if ($status === ContentStatus::Draft->value) {
            $validated['scheduled_publish_at'] = null;
            $validated['scheduled_unpublish_at'] = null;
            $validated['published_at'] = null;
        }

        if (
            array_key_exists('scheduled_unpublish_at', $validated)
            && $validated['scheduled_unpublish_at'] !== null
            && is_string($status)
            && $status !== ContentStatus::Published->value
        ) {
            throw ValidationException::withMessages([
                'scheduled_unpublish_at' => ['Scheduled unpublish is only allowed for published content.'],
            ]);
        }

        return $validated;
    }
}
