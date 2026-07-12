<?php

declare(strict_types=1);

namespace Platform\Billing\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Platform\Billing\Models\Subscription;
use Symfony\Component\HttpFoundation\Response;

final class SubscriptionController
{
    public function show(string $tenantId): JsonResponse
    {
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
            'data' => [
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
            ],
        ]);
    }
}
