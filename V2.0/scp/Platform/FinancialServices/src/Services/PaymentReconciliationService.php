<?php

declare(strict_types=1);

namespace Platform\FinancialServices\Services;

use Illuminate\Support\Carbon;
use Modules\Commerce\Orders\Models\Order;
use Platform\FinancialServices\Enums\RefundStatus;
use Platform\FinancialServices\Models\Refund;

final class PaymentReconciliationService
{
    /**
     * @return array{
     *     period: array{from: string, to: string},
     *     summary: array{
     *         charge_count: int,
     *         refund_count: int,
     *         total_charges_kobo: int,
     *         total_refunds_kobo: int,
     *         net_kobo: int,
     *         currency: string
     *     },
     *     entries: list<array{
     *         type: string,
     *         occurred_at: string,
     *         order_id: string,
     *         order_number: string|null,
     *         reference: string,
     *         amount_kobo: int,
     *         currency: string,
     *         status: string
     *     }>
     * }
     */
    public function buildReport(string $tenantId, Carbon $from, Carbon $to): array
    {
        $from = $from->copy()->startOfDay();
        $to = $to->copy()->endOfDay();

        $entries = [];

        $orders = Order::query()
            ->where('tenant_id', $tenantId)
            ->whereNotNull('paystack_reference')
            ->whereIn('status', [
                Order::STATUS_PAID,
                Order::STATUS_FULFILLED,
                Order::STATUS_REFUNDED,
            ])
            ->whereBetween('created_at', [$from, $to])
            ->orderBy('created_at')
            ->get();

        foreach ($orders as $order) {
            $entries[] = [
                'type' => 'charge',
                'occurred_at' => $order->created_at?->toIso8601String() ?? now()->toIso8601String(),
                'order_id' => $order->id,
                'order_number' => $order->order_number,
                'reference' => (string) $order->paystack_reference,
                'amount_kobo' => $order->total_kobo,
                'currency' => $order->currency,
                'status' => $order->status,
            ];
        }

        $refunds = Refund::query()
            ->where('tenant_id', $tenantId)
            ->where('status', RefundStatus::Completed)
            ->where(function ($query) use ($from, $to): void {
                $query->whereBetween('processed_at', [$from, $to])
                    ->orWhere(function ($nested) use ($from, $to): void {
                        $nested->whereNull('processed_at')
                            ->whereBetween('created_at', [$from, $to]);
                    });
            })
            ->orderByRaw('COALESCE(processed_at, created_at) asc')
            ->get();

        $orderNumbers = Order::query()
            ->whereIn('id', $refunds->pluck('order_id')->unique()->filter()->all())
            ->pluck('order_number', 'id');

        foreach ($refunds as $refund) {
            $occurredAt = $refund->processed_at ?? $refund->created_at;

            $entries[] = [
                'type' => 'refund',
                'occurred_at' => $occurredAt?->toIso8601String() ?? now()->toIso8601String(),
                'order_id' => $refund->order_id,
                'order_number' => $orderNumbers->get($refund->order_id),
                'reference' => $refund->gateway_refund_reference ?? $refund->paystack_reference,
                'amount_kobo' => $refund->amount_kobo,
                'currency' => $refund->currency,
                'status' => $refund->status->value,
            ];
        }

        usort($entries, static fn (array $left, array $right): int => strcmp($left['occurred_at'], $right['occurred_at']));

        $totalCharges = array_sum(array_map(
            static fn (array $entry): int => $entry['type'] === 'charge' ? $entry['amount_kobo'] : 0,
            $entries,
        ));
        $totalRefunds = array_sum(array_map(
            static fn (array $entry): int => $entry['type'] === 'refund' ? $entry['amount_kobo'] : 0,
            $entries,
        ));

        return [
            'period' => [
                'from' => $from->toDateString(),
                'to' => $to->toDateString(),
            ],
            'summary' => [
                'charge_count' => count(array_filter($entries, static fn (array $entry): bool => $entry['type'] === 'charge')),
                'refund_count' => count(array_filter($entries, static fn (array $entry): bool => $entry['type'] === 'refund')),
                'total_charges_kobo' => $totalCharges,
                'total_refunds_kobo' => $totalRefunds,
                'net_kobo' => $totalCharges - $totalRefunds,
                'currency' => 'NGN',
            ],
            'entries' => $entries,
        ];
    }

    /**
     * @param  array{
     *     period: array{from: string, to: string},
     *     summary: array<string, mixed>,
     *     entries: list<array<string, mixed>>
     * }  $report
     */
    public function toCsv(array $report): string
    {
        $lines = [
            'type,occurred_at,order_number,order_id,reference,amount_kobo,currency,status',
        ];

        foreach ($report['entries'] as $entry) {
            $lines[] = implode(',', [
                $this->csvValue((string) $entry['type']),
                $this->csvValue((string) $entry['occurred_at']),
                $this->csvValue((string) ($entry['order_number'] ?? '')),
                $this->csvValue((string) $entry['order_id']),
                $this->csvValue((string) $entry['reference']),
                (string) $entry['amount_kobo'],
                $this->csvValue((string) $entry['currency']),
                $this->csvValue((string) $entry['status']),
            ]);
        }

        $lines[] = '';
        $lines[] = 'summary_field,value';
        $lines[] = 'period_from,'.$this->csvValue($report['period']['from']);
        $lines[] = 'period_to,'.$this->csvValue($report['period']['to']);
        $lines[] = 'charge_count,'.(string) $report['summary']['charge_count'];
        $lines[] = 'refund_count,'.(string) $report['summary']['refund_count'];
        $lines[] = 'total_charges_kobo,'.(string) $report['summary']['total_charges_kobo'];
        $lines[] = 'total_refunds_kobo,'.(string) $report['summary']['total_refunds_kobo'];
        $lines[] = 'net_kobo,'.(string) $report['summary']['net_kobo'];

        return implode("\n", $lines)."\n";
    }

    private function csvValue(string $value): string
    {
        if (str_contains($value, ',') || str_contains($value, '"') || str_contains($value, "\n")) {
            return '"'.str_replace('"', '""', $value).'"';
        }

        return $value;
    }
}
