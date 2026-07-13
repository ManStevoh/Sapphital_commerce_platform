<?php

declare(strict_types=1);

namespace Platform\Ai\Services;

use Platform\Ai\Models\AiPromptTemplate;
use Platform\Ai\Models\AiUsageEvent;
use Platform\Billing\Models\Subscription;
use Platform\Tenancy\Models\Tenant;
use Symfony\Component\HttpKernel\Exception\HttpException;

final class AiAccessPolicy
{
    public function assertEnabled(string $tenantId): void
    {
        $tenant = Tenant::query()->find($tenantId);

        if ($tenant === null) {
            throw new HttpException(404, 'Tenant not found.');
        }

        $settings = is_array($tenant->settings) ? $tenant->settings : [];

        if (($settings['ai_enabled'] ?? true) === false) {
            throw new HttpException(403, 'AI features are disabled for this store.');
        }
    }

    public function assertWithinDailyLimit(string $tenantId): void
    {
        $planSlug = $this->planSlug($tenantId);
        $limit = (int) (config('ai.daily_limits.'.$planSlug)
            ?? config('ai.daily_limits.default', 50));

        $used = AiUsageEvent::query()
            ->where('tenant_id', $tenantId)
            ->where('occurred_at', '>=', now()->startOfDay())
            ->count();

        if ($used >= $limit) {
            throw new HttpException(429, "Daily AI request limit ({$limit}) reached for plan {$planSlug}.");
        }
    }

    public function activeTemplate(string $featureKey): AiPromptTemplate
    {
        $template = AiPromptTemplate::query()
            ->where('feature_key', $featureKey)
            ->where('is_active', true)
            ->orderByDesc('version')
            ->first();

        if ($template === null) {
            throw new HttpException(503, "Prompt template missing for feature {$featureKey}.");
        }

        return $template;
    }

    /**
     * @param  array<string, string>  $vars
     */
    public function render(string $template, array $vars): string
    {
        $rendered = $template;

        foreach ($vars as $key => $value) {
            $rendered = str_replace('{{'.$key.'}}', $value, $rendered);
        }

        return $rendered;
    }

    private function planSlug(string $tenantId): string
    {
        $subscription = Subscription::query()
            ->where('tenant_id', $tenantId)
            ->with('plan')
            ->latest()
            ->first();

        $slug = $subscription?->plan?->slug;

        return is_string($slug) && $slug !== '' ? $slug : 'starter';
    }
}
