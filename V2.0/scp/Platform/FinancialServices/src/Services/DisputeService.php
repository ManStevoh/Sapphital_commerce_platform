<?php

declare(strict_types=1);

namespace Platform\FinancialServices\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Modules\Commerce\Orders\Models\Order;
use Platform\FinancialServices\Enums\DisputeStatus;
use Platform\FinancialServices\Models\Dispute;

final class DisputeService
{
    /**
     * @return array{created: bool, dispute: Dispute|null}
     */
    public function openFromPaystackWebhook(
        string $transactionReference,
        string $providerCaseId,
        int $amountKobo,
        string $currency = 'NGN',
    ): array {
        if ($transactionReference === '' || $providerCaseId === '') {
            return ['created' => false, 'dispute' => null];
        }

        $existing = Dispute::query()
            ->where('provider', 'paystack')
            ->where('provider_case_id', $providerCaseId)
            ->first();

        if ($existing !== null) {
            return ['created' => false, 'dispute' => $existing];
        }

        $order = Order::query()
            ->where('paystack_reference', $transactionReference)
            ->first();

        if ($order === null) {
            return ['created' => false, 'dispute' => null];
        }

        $dispute = Dispute::query()->create([
            'tenant_id' => $order->tenant_id,
            'order_id' => $order->id,
            'type' => 'chargeback',
            'provider' => 'paystack',
            'status' => DisputeStatus::Open,
            'provider_case_id' => $providerCaseId,
            'amount_kobo' => $amountKobo > 0 ? $amountKobo : $order->total_kobo,
            'currency' => $currency !== '' ? $currency : $order->currency,
            'paystack_reference' => $transactionReference,
            'due_at' => now()->addDays(2),
        ]);

        return ['created' => true, 'dispute' => $dispute];
    }

    public function resolveFromPaystackWebhook(
        string $providerCaseId,
        DisputeStatus $status,
    ): ?Dispute {
        $dispute = Dispute::query()
            ->where('provider', 'paystack')
            ->where('provider_case_id', $providerCaseId)
            ->first();

        if ($dispute === null) {
            return null;
        }

        $dispute->update([
            'status' => $status,
            'resolved_at' => now(),
        ]);

        return $dispute->fresh();
    }

    /**
     * @throws ValidationException
     */
    public function resolve(string $tenantId, string $disputeId, DisputeStatus $status): Dispute
    {
        if (! in_array($status, [DisputeStatus::Won, DisputeStatus::Lost, DisputeStatus::Withdrawn], true)) {
            throw ValidationException::withMessages([
                'status' => ['Dispute can only be resolved to won, lost, or withdrawn.'],
            ]);
        }

        return DB::transaction(function () use ($tenantId, $disputeId, $status): Dispute {
            $dispute = Dispute::query()
                ->where('tenant_id', $tenantId)
                ->where('id', $disputeId)
                ->firstOrFail();

            if (! in_array($dispute->status, [DisputeStatus::Open, DisputeStatus::UnderReview], true)) {
                throw ValidationException::withMessages([
                    'dispute' => ['Only open disputes can be resolved.'],
                ]);
            }

            $dispute->update([
                'status' => $status,
                'resolved_at' => now(),
            ]);

            return $dispute->fresh();
        });
    }
}
