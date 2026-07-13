<?php

declare(strict_types=1);

namespace Tests\Feature\Launch;

use Tests\TestCase;

final class LaunchReadinessCommandTest extends TestCase
{
    public function test_launch_readiness_command_passes_in_testing(): void
    {
        $this->artisan('scp:launch-readiness')
            ->assertSuccessful();
    }

    public function test_openapi_spec_contains_phase1_payment_paths(): void
    {
        $path = base_path('docs/openapi/nigeria-ga-v1.yaml');

        $this->assertFileExists($path);

        $contents = (string) file_get_contents($path);

        foreach ([
            '/v1/commerce/storefront/settings/payments/credentials:',
            '/v1/webhooks/flutterwave:',
            '/v1/platform/financial-services/reconciliation:',
            'PaymentCredentialsStatus',
        ] as $needle) {
            $this->assertStringContainsString($needle, $contents);
        }
    }
}
