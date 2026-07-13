<?php

declare(strict_types=1);

namespace Platform\FinancialServices\Services;

use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Modules\Commerce\Orders\Models\Order;
use Platform\FinancialServices\Enums\RefundStatus;
use Platform\FinancialServices\Models\Dispute;
use Platform\FinancialServices\Models\Refund;

final class RefundService
{
    public function __construct(
        private readonly PaymentGatewayResolver $gatewayResolver,
        private readonly TenantPaymentProviderService $tenantPaymentProvider,
    ) {}

    /**
     * @return array{refund: Refund, order: Order}
     *
     * @throws ModelNotFoundException
     * @throws ValidationException
     */
    public function refundOrder(
        string $tenantId,
        string $orderId,
        ?int $amountKobo = null,
        ?string $reason = null,
    ): array {
        return DB::transaction(function () use ($tenantId, $orderId, $amountKobo, $reason): array {
            $order = Order::query()
                ->where('tenant_id', $tenantId)
                ->where('id', $orderId)
                ->lockForUpdate()
                ->firstOrFail();

            if ($order->status !== Order::STATUS_PAID) {
                throw ValidationException::withMessages([
                    'order_id' => ['Only paid orders can be refunded.'],
                ]);
            }

            if (Dispute::hasOpenDisputeForOrder($tenantId, $order->id)) {
                throw ValidationException::withMessages([
                    'order_id' => ['Refunds are blocked while an open dispute exists on this order.'],
                ]);
            }

            $paymentReference = $order->paystack_reference;

            if (! is_string($paymentReference) || $paymentReference === '') {
                throw ValidationException::withMessages([
                    'order_id' => ['Order has no payment reference for refund.'],
                ]);
            }

            $alreadyRefunded = (int) Refund::query()
                ->where('tenant_id', $tenantId)
                ->where('order_id', $order->id)
                ->where('status', RefundStatus::Completed)
                ->sum('amount_kobo');

            $remainingKobo = $order->total_kobo - $alreadyRefunded;

            if ($remainingKobo <= 0) {
                throw ValidationException::withMessages([
                    'order_id' => ['Order has already been fully refunded.'],
                ]);
            }

            $refundAmount = $amountKobo ?? $remainingKobo;

            if ($refundAmount > $remainingKobo) {
                throw ValidationException::withMessages([
                    'amount_kobo' => ['Refund amount exceeds remaining refundable balance.'],
                ]);
            }

            $refund = Refund::query()->create([
                'tenant_id' => $tenantId,
                'order_id' => $order->id,
                'amount_kobo' => $refundAmount,
                'currency' => $order->currency,
                'status' => RefundStatus::Pending,
                'reason' => $reason,
                'paystack_reference' => $paymentReference,
            ]);

            $response = $this->gatewayResolver
                ->resolveForTenant($tenantId, $this->tenantPaymentProvider->forTenant($tenantId))
                ->refundPayment($paymentReference, $refundAmount);

            if (! ($response['status'] ?? false)) {
                $refund->update(['status' => RefundStatus::Failed]);

                throw ValidationException::withMessages([
                    'refund' => [(string) ($response['message'] ?? 'Refund failed at payment gateway.')],
                ]);
            }

            $data = is_array($response['data'] ?? null) ? $response['data'] : [];
            $gatewayReference = is_string($data['reference'] ?? null) ? $data['reference'] : null;

            $refund->update([
                'status' => RefundStatus::Completed,
                'gateway_refund_reference' => $gatewayReference,
                'processed_at' => now(),
            ]);

            $totalRefunded = $alreadyRefunded + $refundAmount;

            if ($totalRefunded >= $order->total_kobo) {
                $order->update(['status' => Order::STATUS_REFUNDED]);
            }

            $freshOrder = $order->fresh(['items']);
            $freshRefund = $refund->fresh();
            $this->maybeNotifyRefundConfirmation($freshOrder, $freshRefund);

            return [
                'refund' => $freshRefund,
                'order' => $freshOrder,
            ];
        });
    }

    private function maybeNotifyRefundConfirmation(Order $order, Refund $refund): void
    {
        $notifierClass = 'Platform\\Notifications\\Services\\RefundConfirmationNotifier';

        if (! class_exists($notifierClass)) {
            return;
        }

        app($notifierClass)->send($order, $refund);
    }
}
