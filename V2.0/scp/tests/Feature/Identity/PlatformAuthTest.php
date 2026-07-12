<?php

declare(strict_types=1);

namespace Tests\Feature\Identity;

use Platform\Identity\Models\PlatformAdmin;

final class PlatformAuthTest extends IdentityTestCase
{
    public function test_platform_login_succeeds_with_valid_credentials(): void
    {
        PlatformAdmin::query()->create([
            'email' => 'admin@sapphital.test',
            'password' => 'platform-secret',
        ]);

        $response = $this->postJson('/api/v1/auth/platform/login', [
            'email' => 'admin@sapphital.test',
            'password' => 'platform-secret',
        ]);

        $response->assertOk()
            ->assertJsonStructure([
                'token',
                'token_type',
            ])
            ->assertJson([
                'token_type' => 'Bearer',
            ]);

        $this->assertNotEmpty($response->json('token'));
    }
}
