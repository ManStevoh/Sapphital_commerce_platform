<?php

declare(strict_types=1);

namespace Platform\FinancialServices\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Platform\FinancialServices\Enums\DisputeStatus;
use Platform\FinancialServices\Models\Dispute;

final class DisputeDeadlineAlertService
{
    public function __construct(
        private readonly DisputeDeadlineNotifier $notifier,
    ) {}

    /**
     * @return list<array{id: string, tenant_id: string, order_id: string, due_at: string|null}>
     */
    public function dueWithinHours(int $hours = 48): array
    {
        $deadline = now()->addHours(max(1, $hours));

        /** @var Collection<int, Dispute> $disputes */
        $disputes = Dispute::query()
            ->whereIn('status', [DisputeStatus::Open, DisputeStatus::UnderReview])
            ->whereNotNull('due_at')
            ->whereNull('deadline_alerted_at')
            ->where('due_at', '<=', $deadline)
            ->orderBy('due_at')
            ->get();

        return $disputes->map(static fn (Dispute $dispute): array => [
            'id' => $dispute->id,
            'tenant_id' => $dispute->tenant_id,
            'order_id' => $dispute->order_id,
            'due_at' => $dispute->due_at?->toIso8601String(),
        ])->all();
    }

    /**
     * @return list<array{id: string, tenant_id: string, order_id: string, due_at: string|null}>
     */
    public function run(int $hours = 48): array
    {
        $deadline = now()->addHours(max(1, $hours));

        /** @var Collection<int, Dispute> $disputes */
        $disputes = Dispute::query()
            ->whereIn('status', [DisputeStatus::Open, DisputeStatus::UnderReview])
            ->whereNotNull('due_at')
            ->whereNull('deadline_alerted_at')
            ->where('due_at', '<=', $deadline)
            ->orderBy('due_at')
            ->get();

        $alerted = [];

        foreach ($disputes as $dispute) {
            $this->notifier->notify($dispute);

            $dispute->update(['deadline_alerted_at' => now()]);

            if (app()->environment('testing')) {
                Log::info('dispute.deadline.alert', [
                    'id' => $dispute->id,
                    'tenant_id' => $dispute->tenant_id,
                    'order_id' => $dispute->order_id,
                    'due_at' => $dispute->due_at?->toIso8601String(),
                ]);
            } else {
                Log::warning('dispute.deadline.alert', [
                    'id' => $dispute->id,
                    'tenant_id' => $dispute->tenant_id,
                    'order_id' => $dispute->order_id,
                    'due_at' => $dispute->due_at?->toIso8601String(),
                ]);
            }

            $alerted[] = [
                'id' => $dispute->id,
                'tenant_id' => $dispute->tenant_id,
                'order_id' => $dispute->order_id,
                'due_at' => $dispute->due_at?->toIso8601String(),
            ];
        }

        return $alerted;
    }
}
