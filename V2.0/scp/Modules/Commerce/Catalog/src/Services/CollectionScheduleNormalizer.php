<?php

declare(strict_types=1);

namespace Modules\Commerce\Catalog\Services;

use Illuminate\Validation\ValidationException;
use Modules\Commerce\Catalog\Enums\CollectionStatus;

final class CollectionScheduleNormalizer
{
    /**
     * @param  array<string, mixed>  $validated
     * @return array<string, mixed>
     */
    public function normalize(array $validated): array
    {
        $status = $validated['status'] ?? null;

        if ($status === CollectionStatus::Scheduled->value) {
            if (! isset($validated['starts_at']) || $validated['starts_at'] === null) {
                throw ValidationException::withMessages([
                    'starts_at' => ['A start time is required when status is scheduled.'],
                ]);
            }

            $validated['published_at'] = null;
        }

        if ($status === CollectionStatus::Published->value) {
            $validated['published_at'] = $validated['published_at'] ?? now()->toIso8601String();
        }

        if ($status === CollectionStatus::Draft->value) {
            $validated['starts_at'] = null;
            $validated['ends_at'] = null;
            $validated['published_at'] = null;
        }

        if (
            array_key_exists('ends_at', $validated)
            && $validated['ends_at'] !== null
            && is_string($status)
            && ! in_array($status, [
                CollectionStatus::Published->value,
                CollectionStatus::Scheduled->value,
            ], true)
        ) {
            throw ValidationException::withMessages([
                'ends_at' => ['End date is only allowed for published or scheduled collections.'],
            ]);
        }

        return $validated;
    }
}
