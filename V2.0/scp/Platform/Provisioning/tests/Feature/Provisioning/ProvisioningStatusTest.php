<?php

declare(strict_types=1);

namespace Platform\Provisioning\Tests\Feature\Provisioning;

use Platform\Provisioning\Tests\TestCase;

final class ProvisioningStatusTest extends TestCase
{
    public function test_status_endpoint_shows_completed_after_sync_queue(): void
    {
        $signup = $this->postJson('/api/v1/signup', [
            'email' => 'owner@example.com',
            'password' => 'securepassword12',
            'store_name' => 'Status Test Store',
            'plan_slug' => 'starter',
        ]);

        $signup->assertAccepted();

        $tenantId = $signup->json('tenant_id');

        $response = $this->getJson('/api/v1/provisioning/'.$tenantId.'/status');

        $response->assertOk()
            ->assertJson([
                'tenant_id' => $tenantId,
                'status' => 'completed',
            ])
            ->assertJsonStructure([
                'provisioning_run_id',
                'steps' => [
                    'create_default_store_settings',
                    'assign_theme',
                    'seed_sample_products',
                    'create_pages',
                    'configure_paystack_placeholder',
                ],
            ]);

        $steps = $response->json('steps');

        $this->assertTrue($steps['create_default_store_settings']['completed']);
        $this->assertSame('NGN', $steps['create_default_store_settings']['data']['currency']);
        $this->assertSame('Africa/Lagos', $steps['create_default_store_settings']['data']['timezone']);
        $this->assertTrue($steps['assign_theme']['completed']);
        $this->assertSame('scp-dawn', $steps['assign_theme']['data']['theme']);
        $this->assertTrue($steps['seed_sample_products']['completed']);
        $this->assertCount(3, $steps['seed_sample_products']['data']['products']);
        $this->assertTrue($steps['create_pages']['completed']);
        $this->assertTrue($steps['configure_paystack_placeholder']['completed']);
        $this->assertNotNull($response->json('completed_at'));
    }

    public function test_status_endpoint_returns_404_for_unknown_tenant(): void
    {
        $response = $this->getJson('/api/v1/provisioning/00000000-0000-0000-0000-000000000099/status');

        $response->assertNotFound();
    }
}
