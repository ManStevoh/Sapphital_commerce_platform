<?php

declare(strict_types=1);

namespace Tests\Feature\Provisioning;

use Illuminate\Support\Str;
use Tests\Feature\PlatformTestCase;

final class ProvisioningStatusTest extends PlatformTestCase
{
    public function test_status_endpoint_shows_completed_after_sync_queue(): void
    {
        $signup = $this->postJson('/api/v1/signup', [
            'email' => 'status@example.com',
            'password' => 'password123',
            'store_name' => 'Status Shop',
            'plan_slug' => 'starter',
        ]);

        $tenantId = $signup->json('tenant_id');

        $response = $this->getJson("/api/v1/provisioning/{$tenantId}/status");

        $response->assertOk()
            ->assertJsonPath('status', 'completed')
            ->assertJsonStructure(['steps']);
    }

    public function test_status_endpoint_returns_404_for_unknown_tenant(): void
    {
        $response = $this->getJson('/api/v1/provisioning/'.Str::uuid().'/status');

        $response->assertNotFound();
    }
}
