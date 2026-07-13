<?php

declare(strict_types=1);

namespace Platform\Billing\Services;

use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Platform\Billing\Enums\BillingPaymentIntentStatus;
use Platform\Billing\Enums\SubscriptionStatus;
use Platform\Billing\Models\BillingPaymentIntent;
use Platform\Billing\Models\Subscription;
use Platform\FinancialServices\Contracts\PaymentGatewayInterface;

final class BillingSubscriptionPaymentService
{
    public function __construct(
        private readonly PaymentGatewayInterface $gateway,
        private readonly SubscriptionLifecycleService $lifecycle,
    ) {}

    /**
     * @return array{authorization_url: string, reference: string}
     */
    public function initializePayment(string $tenantId, string $email): array
    {
        $subscription = Subscription::query()
            ->where('tenant_id', $tenantId)
            ->with('plan')
            ->first();

        if ($subscription === null || $subscription->plan === null) {
            throw ValidationException::withMessages([
                'subscription' => ['Subscription not found.'],
            ]);
        }

        if (! in_array($subscription->status, [
            SubscriptionStatus::Trial,
            SubscriptionStatus::PastDue,
            SubscriptionStatus::Suspended,
        ], true)) {
            throw ValidationException::withMessages([
                'subscription' => ['Subscription is not eligible for payment.'],
            ]);
        }

        $reference = 'saas_'.$tenantId.'_'.Str::lower(Str::random(8));
        $amountKobo = $subscription->plan->price_ngn;

        $response = $this->gateway->initializePayment([
            'email' => $email,
            'amount' => $amountKobo,
            'reference' => $reference,
            'metadata' => [
                'billing_type' => 'platform_subscription',
                'tenant_id' => $tenantId,
                'subscription_id' => $subscription->id,
            ],
        ]);

        if (! ($response['status'] ?? false)) {
            throw ValidationException::withMessages([
                'payment' => [(string) ($response['message'] ?? 'Payment initialization failed.')],
            ]);
        }

        $data = is_array($response['data'] ?? null) ? $response['data'] : [];
        $authorizationUrl = $data['authorization_url'] ?? null;
        $gatewayReference = is_string($data['reference'] ?? null) ? $data['reference'] : $reference;

        if (! is_string($authorizationUrl) || $authorizationUrl === '') {
            throw ValidationException::withMessages([
                'payment' => ['Payment gateway did not return an authorization URL.'],
            ]);
        }

        BillingPaymentIntent::query()->create([
            'tenant_id' => $tenantId,
            'subscription_id' => $subscription->id,
            'paystack_reference' => $gatewayReference,
            'amount_kobo' => $amountKobo,
            'status' => BillingPaymentIntentStatus::Pending,
        ]);

        return [
            'authorization_url' => $authorizationUrl,
            'reference' => $gatewayReference,
        ];
    }

    public function tenantIdForPaymentReference(string $reference): ?string
    {
        if ($reference === '') {
            return null;
        }

        $intent = BillingPaymentIntent::query()
            ->where('paystack_reference', $reference)
            ->first();

        return $intent?->tenant_id;
    }

    public function tryHandleChargeSuccess(string $reference, int $amountKobo): bool
    {
        if ($reference === '') {
            return false;
        }

        $intent = BillingPaymentIntent::query()
            ->where('paystack_reference', $reference)
            ->where('status', BillingPaymentIntentStatus::Pending)
            ->first();

        if ($intent === null) {
            return false;
        }

        if ($amountKobo > 0 && $amountKobo !== $intent->amount_kobo) {
            $intent->update(['status' => BillingPaymentIntentStatus::Failed]);

            return false;
        }

        $this->lifecycle->activateAfterPayment($intent->tenant_id, $reference);

        $intent->update(['status' => BillingPaymentIntentStatus::Completed]);

        return true;
    }
}
