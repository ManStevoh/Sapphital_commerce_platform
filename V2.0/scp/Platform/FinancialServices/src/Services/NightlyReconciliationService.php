<?php

declare(strict_types=1);

namespace Platform\FinancialServices\Services;

use Illuminate\Support\Facades\Artisan;
use Modules\Commerce\Checkout\Models\CheckoutSession;
use Modules\Commerce\Orders\Models\Order;
use Platform\FinancialServices\Services\PaymentOrchestrator;

final class NightlyReconciliationService
{
    public function __construct(
        private readonly PaymentOrchestrator $paymentOrchestrator,
    ) {}

    /**
     * @return array{
     *     pending_reconciled: int,
     *     pending_failed: int,
     *     paid_orders_missing_completed_session: int,
     *     completed_sessions_missing_order: int
     * }
     */
    public function run(int $staleMinutes = 15): array
    {
        $pending = $this->reconcileStalePendingSessions($staleMinutes);

        return array_merge($pending, [
            'paid_orders_missing_completed_session' => $this->countPaidOrdersWithIncompleteSession(),
            'completed_sessions_missing_order' => $this->countCompletedSessionsWithoutOrder(),
        ]);
    }

    /**
     * @return array{pending_reconciled: int, pending_failed: int}
     */
    private function reconcileStalePendingSessions(int $minutes): array
    {
        $sessions = CheckoutSession::query()
            ->where('status', CheckoutSession::STATUS_PENDING)
            ->whereNotNull('paystack_reference')
            ->where('updated_at', '<=', now()->subMinutes(max(1, $minutes)))
            ->orderBy('updated_at')
            ->limit(100)
            ->get();

        $reconciled = 0;
        $failed = 0;

        foreach ($sessions as $session) {
            try {
                $result = $this->paymentOrchestrator->verifyCheckoutPayment(
                    $session->tenant_id,
                    (string) $session->paystack_reference,
                );

                if (($result['status'] ?? '') === CheckoutSession::STATUS_COMPLETED) {
                    $reconciled++;
                }
            } catch (\Throwable) {
                $failed++;
            }
        }

        return [
            'pending_reconciled' => $reconciled,
            'pending_failed' => $failed,
        ];
    }

    private function countPaidOrdersWithIncompleteSession(): int
    {
        return Order::query()
            ->where('status', Order::STATUS_PAID)
            ->whereNotNull('checkout_session_id')
            ->whereHas('checkoutSession', function ($query): void {
                $query->where('status', '!=', CheckoutSession::STATUS_COMPLETED);
            })
            ->count();
    }

    private function countCompletedSessionsWithoutOrder(): int
    {
        return CheckoutSession::query()
            ->where('status', CheckoutSession::STATUS_COMPLETED)
            ->whereDoesntHave('order')
            ->count();
    }
}
