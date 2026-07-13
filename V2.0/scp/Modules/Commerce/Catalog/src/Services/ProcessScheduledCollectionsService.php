<?php

declare(strict_types=1);

namespace Modules\Commerce\Catalog\Services;

use Illuminate\Support\Carbon;
use Modules\Commerce\Catalog\Enums\CollectionStatus;
use Modules\Commerce\Catalog\Models\Collection;

final class ProcessScheduledCollectionsService
{
    public function run(?Carbon $asOf = null): int
    {
        $asOf ??= now();
        $processed = 0;

        $processed += $this->publishDue($asOf);
        $processed += $this->unpublishDue($asOf);

        return $processed;
    }

    private function publishDue(Carbon $asOf): int
    {
        $collections = Collection::query()
            ->where('status', CollectionStatus::Scheduled)
            ->whereNotNull('starts_at')
            ->where('starts_at', '<=', $asOf)
            ->limit(500)
            ->get();

        foreach ($collections as $collection) {
            $collection->update([
                'status' => CollectionStatus::Published,
                'published_at' => $collection->starts_at ?? $asOf,
            ]);
        }

        return $collections->count();
    }

    private function unpublishDue(Carbon $asOf): int
    {
        $collections = Collection::query()
            ->where('status', CollectionStatus::Published)
            ->whereNotNull('ends_at')
            ->where('ends_at', '<=', $asOf)
            ->limit(500)
            ->get();

        foreach ($collections as $collection) {
            $collection->update([
                'status' => CollectionStatus::Draft,
                'published_at' => null,
                'ends_at' => null,
            ]);
        }

        return $collections->count();
    }
}
