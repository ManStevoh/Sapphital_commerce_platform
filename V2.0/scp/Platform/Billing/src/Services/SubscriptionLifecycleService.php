<?php

declare(strict_types=1);

namespace Platform\Billing\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Platform\Billing\Enums\InvoiceStatus;
use Platform\Billing\Enums\SubscriptionStatus;
use Platform\Billing\Models\Invoice;
use Platform\Billing\Models\Subscription;
use Platform\Tenancy\Models\Tenant;

final class SubscriptionLifecycleService
{
    public function __construct(
        private readonly BillingTaxService $billingTax,
    ) {}

    /**
     * Convert trial or past_due subscription to active after platform fee collection.
     *
     * @return array{subscription: Subscription, invoice: Invoice|null}
     */
    public function activateAfterPayment(string $tenantId, ?string $paystackReference = null): array
    {
        return DB::transaction(function () use ($tenantId, $paystackReference): array {
            $subscription = Subscription::query()
                ->where('tenant_id', $tenantId)
                ->with('plan')
                ->lockForUpdate()
                ->first();

            if ($subscription === null) {
                throw ValidationException::withMessages([
                    'subscription' => ['Subscription not found.'],
                ]);
            }

            if ($subscription->status === SubscriptionStatus::Active) {
                return [
                    'subscription' => $subscription->fresh(['plan']),
                    'invoice' => null,
                ];
            }

            if (! in_array($subscription->status, [
                SubscriptionStatus::Trial,
                SubscriptionStatus::PastDue,
                SubscriptionStatus::Suspended,
            ], true)) {
                throw ValidationException::withMessages([
                    'subscription' => ['Subscription cannot be activated from its current status.'],
                ]);
            }

            if ($subscription->plan === null) {
                throw ValidationException::withMessages([
                    'plan' => ['Subscription plan not found.'],
                ]);
            }

            $periodStart = now()->startOfDay();
            $periodEnd = now()->addMonth()->startOfDay();

            $subscription->forceFill([
                'status' => SubscriptionStatus::Active,
                'past_due_at' => null,
                'current_period_end' => $periodEnd,
            ])->save();

            $this->restoreTenantAfterPayment($subscription->tenant_id);

            $invoice = $this->createPaidInvoice(
                $subscription,
                $periodStart,
                $periodEnd,
                $paystackReference,
            );

            return [
                'subscription' => $subscription->fresh(['plan']),
                'invoice' => $invoice,
            ];
        });
    }

    public function processExpiredTrials(): int
    {
        $expired = Subscription::query()
            ->where('status', SubscriptionStatus::Trial)
            ->whereNotNull('trial_ends_at')
            ->where('trial_ends_at', '<=', now())
            ->get();

        $count = 0;

        foreach ($expired as $subscription) {
            $subscription->forceFill([
                'status' => SubscriptionStatus::PastDue,
                'past_due_at' => now(),
            ])->save();
            $count++;
        }

        return $count;
    }

    public function suspendOverdueSubscriptions(int $graceDays = 14): int
    {
        $cutoff = now()->subDays(max(1, $graceDays));

        $overdue = Subscription::query()
            ->where('status', SubscriptionStatus::PastDue)
            ->whereNotNull('past_due_at')
            ->where('past_due_at', '<=', $cutoff)
            ->get();

        $count = 0;

        foreach ($overdue as $subscription) {
            $subscription->forceFill([
                'status' => SubscriptionStatus::Suspended,
            ])->save();

            Tenant::query()
                ->whereKey($subscription->tenant_id)
                ->whereIn('status', ['trial', 'active'])
                ->update(['status' => 'suspended']);

            $count++;
        }

        return $count;
    }

    private function restoreTenantAfterPayment(string $tenantId): void
    {
        Tenant::query()
            ->whereKey($tenantId)
            ->where('status', 'suspended')
            ->update(['status' => 'active']);
    }

    private function createPaidInvoice(
        Subscription $subscription,
        \Illuminate\Support\Carbon $periodStart,
        \Illuminate\Support\Carbon $periodEnd,
        ?string $paystackReference,
    ): Invoice {
        $plan = $subscription->plan;
        $tenant = Tenant::query()->findOrFail($subscription->tenant_id);
        $amounts = $this->billingTax->calculateForTenant($tenant, $plan->price_ngn);

        $lineItems = [
            [
                'description' => $plan->name.' subscription',
                'amount' => $amounts['subtotal'],
            ],
        ];

        if ($amounts['vat_applied']) {
            $lineItems[] = [
                'description' => sprintf('VAT (%.1f%%)', $amounts['vat_rate'] * 100),
                'amount' => $amounts['tax'],
            ];
        }

        return Invoice::query()->create([
            'tenant_id' => $subscription->tenant_id,
            'number' => $this->nextInvoiceNumber($subscription->tenant_id),
            'status' => InvoiceStatus::Paid,
            'currency' => 'NGN',
            'subtotal' => $amounts['subtotal'],
            'tax' => $amounts['tax'],
            'total' => $amounts['total'],
            'period_start' => $periodStart,
            'period_end' => $periodEnd,
            'lines' => $lineItems,
            'paystack_reference' => $paystackReference,
        ]);
    }

    private function nextInvoiceNumber(string $tenantId): string
    {
        $sequence = Invoice::query()
            ->where('tenant_id', $tenantId)
            ->count() + 1;

        return sprintf('INV-%s-%05d', now()->format('Y'), $sequence);
    }
}
