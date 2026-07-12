<?php

declare(strict_types=1);

namespace Platform\Billing\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Platform\Billing\Models\Plan;

final class PlanController
{
    public function index(): JsonResponse
    {
        $plans = Plan::query()
            ->orderBy('price_ngn')
            ->get()
            ->map(static fn (Plan $plan): array => [
                'id' => $plan->id,
                'slug' => $plan->slug,
                'name' => $plan->name,
                'price_ngn' => $plan->price_ngn,
                'product_limit' => $plan->product_limit,
                'staff_limit' => $plan->staff_limit,
                'custom_domain' => $plan->custom_domain,
            ]);

        return response()->json([
            'data' => $plans,
        ]);
    }
}
