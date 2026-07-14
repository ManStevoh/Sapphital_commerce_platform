<?php

declare(strict_types=1);

namespace App\Support\Ops;

final class ErrorBudgetCalculator
{
    /**
     * @param  array<string, float>  $actuals
     * @return array<string, mixed>
     */
    public function report(array $actuals): array
    {
        $slos = [
            'availability' => 99.9,
            'checkout_availability' => 99.95,
            'webhook_delivery' => 99.5,
        ];

        $results = [];
        $lowestRemaining = 100.0;

        foreach ($slos as $name => $target) {
            $actual = $actuals[$name] ?? 100.0;
            $allowedError = max(0.0001, 100.0 - $target);
            $actualError = max(0.0, 100.0 - $actual);
            $remaining = max(0.0, min(100.0, (($allowedError - $actualError) / $allowedError) * 100.0));
            $lowestRemaining = min($lowestRemaining, $remaining);

            $results[$name] = [
                'target' => $target,
                'actual' => $actual,
                'budget_remaining_percent' => round($remaining, 2),
            ];
        }

        return [
            'window' => 'calendar_month',
            'slos' => $results,
            'lowest_budget_remaining_percent' => round($lowestRemaining, 2),
            'policy' => $this->policy($lowestRemaining),
        ];
    }

    /**
     * @return array{state: string, action: string}
     */
    private function policy(float $remaining): array
    {
        if ($remaining <= 0.0) {
            return [
                'state' => 'exhausted',
                'action' => 'Feature freeze; executive sign-off required for any deploy.',
            ];
        }

        if ($remaining < 25.0) {
            return [
                'state' => 'reliability_sprint',
                'action' => 'Pause feature work and prioritize reliability fixes.',
            ];
        }

        if ($remaining <= 50.0) {
            return [
                'state' => 'guarded',
                'action' => 'Freeze risky changes and require extra review.',
            ];
        }

        return [
            'state' => 'normal',
            'action' => 'Normal release velocity.',
        ];
    }
}
