<?php

declare(strict_types=1);

namespace Platform\FinancialServices\Console;

use Illuminate\Console\Command;
use Modules\Commerce\Checkout\Models\CheckoutSession;
use Platform\FinancialServices\Services\PaymentOrchestrator;

final class ReconcilePendingPaymentsCommand extends Command
{
    protected $signature = 'scp:reconcile-pending-payments {--minutes=15 : Age threshold in minutes}';

    protected $description = 'Verify stale pending checkout sessions against Paystack (missed webhook recovery)';

    public function handle(PaymentOrchestrator $paymentOrchestrator): int
    {
        $minutes = max(1, (int) $this->option('minutes'));

        $sessions = CheckoutSession::query()
            ->where('status', CheckoutSession::STATUS_PENDING)
            ->whereNotNull('paystack_reference')
            ->where('updated_at', '<=', now()->subMinutes($minutes))
            ->orderBy('updated_at')
            ->limit(100)
            ->get();

        if ($sessions->isEmpty()) {
            $this->info('No stale pending checkout sessions found.');

            return self::SUCCESS;
        }

        $reconciled = 0;
        $failed = 0;

        foreach ($sessions as $session) {
            try {
                $result = $paymentOrchestrator->verifyCheckoutPayment(
                    $session->tenant_id,
                    (string) $session->paystack_reference,
                );

                if (($result['status'] ?? '') === CheckoutSession::STATUS_COMPLETED) {
                    $reconciled++;
                    $this->line("Reconciled {$session->id} ({$session->paystack_reference})");
                }
            } catch (\Throwable $exception) {
                $failed++;
                $this->warn("Failed {$session->id}: {$exception->getMessage()}");
            }
        }

        $this->info("Reconciled {$reconciled} session(s); {$failed} failed.");

        return self::SUCCESS;
    }
}
