<?php

declare(strict_types=1);

namespace Platform\Provisioning\Tests\Feature\Provisioning;

use Illuminate\Support\Facades\DB;
use Platform\Provisioning\Tests\TestCase;
use Platform\Tenancy\Models\Tenant;

final class SignupTest extends TestCase
{
    public function test_signup_creates_tenant_and_returns_202(): void
    {
        $response = $this->postJson('/api/v1/signup', [
            'email' => 'merchant@example.com',
            'password' => 'password123',
            'store_name' => 'Lagos Tech Shop',
            'plan_slug' => 'starter',
        ]);

        $response->assertAccepted()
            ->assertJsonStructure([
                'tenant_id',
                'provisioning_run_id',
                'status',
                'poll_url',
            ])
            ->assertJson([
                'status' => 'provisioning',
            ]);

        $tenantId = $response->json('tenant_id');

        $this->assertNotNull($tenantId);
        $this->assertDatabaseHas('tenants', [
            'id' => $tenantId,
            'name' => 'Lagos Tech Shop',
            'slug' => 'lagos-tech-shop',
            'status' => 'trial',
            'country' => 'NG',
        ]);

        $this->assertDatabaseHas('subscriptions', [
            'tenant_id' => $tenantId,
            'status' => 'trial',
        ]);

        $this->assertDatabaseHas('merchant_users', [
            'tenant_id' => $tenantId,
            'email' => 'merchant@example.com',
            'role' => 'owner',
        ]);

        $this->assertDatabaseHas('provisioning_runs', [
            'tenant_id' => $tenantId,
            'status' => 'completed',
        ]);
    }

    public function test_signup_rejects_invalid_plan(): void
    {
        $response = $this->postJson('/api/v1/signup', [
            'email' => 'merchant@example.com',
            'password' => 'password123',
            'store_name' => 'Test Store',
            'plan_slug' => 'nonexistent',
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['plan_slug']);

        $this->assertSame(0, Tenant::query()->count());
        $this->assertSame(0, DB::table('merchant_users')->count());
    }
}
