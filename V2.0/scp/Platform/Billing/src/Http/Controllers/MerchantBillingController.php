<?php

declare(strict_types=1);

namespace Platform\Billing\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Platform\Billing\Models\Invoice;
use Platform\Billing\Models\Subscription;
use Symfony\Component\HttpFoundation\Response;

final class MerchantBillingController
{
    public function subscription(Request $request): JsonResponse
    {
        $tenantId = $this->tenantId($request);

        if ($tenantId === null) {
            return $this->missingTenantResponse();
        }

        $subscription = Subscription::query()
            ->where('tenant_id', $tenantId)
            ->with('plan')
            ->first();

        if ($subscription === null) {
            return response()->json([
                'message' => 'Subscription not found.',
            ], Response::HTTP_NOT_FOUND);
        }

        return response()->json([
            'data' => $this->subscriptionPayload($subscription),
        ]);
    }

    public function invoices(Request $request): JsonResponse
    {
        $tenantId = $this->tenantId($request);

        if ($tenantId === null) {
            return $this->missingTenantResponse();
        }

        $invoices = Invoice::query()
            ->where('tenant_id', $tenantId)
            ->orderByDesc('created_at')
            ->get();

        return response()->json([
            'data' => $invoices->map(static fn (Invoice $invoice): array => [
                'id' => $invoice->id,
                'number' => $invoice->number,
                'status' => $invoice->status->value,
                'currency' => $invoice->currency,
                'subtotal' => $invoice->subtotal,
                'tax' => $invoice->tax,
                'total' => $invoice->total,
                'period_start' => $invoice->period_start?->toDateString(),
                'period_end' => $invoice->period_end?->toDateString(),
                'created_at' => $invoice->created_at?->toIso8601String(),
            ])->values(),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function subscriptionPayload(Subscription $subscription): array
    {
        return [
            'id' => $subscription->id,
            'tenant_id' => $subscription->tenant_id,
            'plan_id' => $subscription->plan_id,
            'status' => $subscription->status->value,
            'trial_ends_at' => $subscription->trial_ends_at?->toIso8601String(),
            'current_period_end' => $subscription->current_period_end?->toIso8601String(),
            'plan' => $subscription->plan === null ? null : [
                'id' => $subscription->plan->id,
                'slug' => $subscription->plan->slug,
                'name' => $subscription->plan->name,
                'price_ngn' => $subscription->plan->price_ngn,
                'product_limit' => $subscription->plan->product_limit,
                'staff_limit' => $subscription->plan->staff_limit,
                'custom_domain' => $subscription->plan->custom_domain,
            ],
        ];
    }

    private function tenantId(Request $request): ?string
    {
        $tenantId = $request->attributes->get('tenant_id');

        return is_string($tenantId) && $tenantId !== '' ? $tenantId : null;
    }

    private function missingTenantResponse(): JsonResponse
    {
        return response()->json([
            'message' => 'Tenant context required.',
        ], Response::HTTP_FORBIDDEN);
    }
}
