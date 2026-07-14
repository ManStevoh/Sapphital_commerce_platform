<?php

declare(strict_types=1);

namespace Tests\Feature;

final class OpsStatusTest extends PlatformTestCase
{
    public function test_public_status_page_payload_lists_components(): void
    {
        $this->getJson('/api/v1/status')
            ->assertOk()
            ->assertJsonPath('page.url', 'https://status.sapphital.com')
            ->assertJsonPath('page.timezone', 'Africa/Lagos')
            ->assertJsonPath('page.overall_status', 'operational')
            ->assertJsonFragment([
                'id' => 'checkout',
                'name' => 'Checkout',
                'status' => 'operational',
            ])
            ->assertJsonFragment([
                'id' => 'webhooks',
                'name' => 'Webhooks',
                'status' => 'operational',
            ]);
    }

    public function test_runbook_and_support_macro_catalogs_are_public_readonly(): void
    {
        $this->getJson('/api/v1/ops/runbooks')
            ->assertOk()
            ->assertJsonFragment([
                'id' => 'RB-003',
                'title' => 'Webhook backlog',
                'owner' => 'Commerce on-call',
            ]);

        $this->getJson('/api/v1/support/macros')
            ->assertOk()
            ->assertJsonFragment([
                'id' => 'custom-domain-dns',
                'title' => '.ng custom domain DNS propagation',
                'category' => 'domains',
            ]);
    }

    public function test_synthetic_checkout_probe_dry_run_outputs_probe_plan(): void
    {
        $this->artisan('ops:synthetic-checkout-probe', [
            '--base-url' => 'https://status-probe.sapphital.test',
            '--dry-run' => true,
        ])
            ->expectsOutput('probe=ready url=https://status-probe.sapphital.test/api/ready')
            ->expectsOutput('probe=themes url=https://status-probe.sapphital.test/api/v1/commerce/storefront/themes')
            ->assertSuccessful();
    }

    public function test_error_budget_report_enforces_freeze_when_budget_exhausted(): void
    {
        $this->artisan('ops:error-budget-report', [
            '--availability' => '99.7',
            '--checkout' => '99.8',
            '--webhooks' => '99.9',
        ])
            ->expectsOutput('policy_state=exhausted')
            ->expectsOutput('policy_action=Feature freeze; executive sign-off required for any deploy.')
            ->assertSuccessful();
    }
}
