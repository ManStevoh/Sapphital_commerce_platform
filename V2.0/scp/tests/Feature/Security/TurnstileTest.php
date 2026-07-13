<?php

declare(strict_types=1);

namespace Tests\Feature\Security;

use Illuminate\Support\Str;
use Platform\Identity\Contracts\BotVerifier;
use Platform\Tenancy\Models\Tenant;
use Tests\Feature\PlatformTestCase;

final class TurnstileTest extends PlatformTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config(['turnstile.enabled' => true]);
    }

    public function test_signup_rejects_missing_turnstile_token_when_enabled(): void
    {
        $this->app->instance(BotVerifier::class, new class implements BotVerifier
        {
            public function verify(string $token, ?string $remoteIp = null): bool
            {
                return $token === 'valid-token';
            }
        });

        $response = $this->postJson('/api/v1/signup', [
            'email' => 'turnstile@example.com',
            'password' => 'securepassword12',
            'store_name' => 'Turnstile Shop',
            'plan_slug' => 'starter',
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['cf-turnstile-response']);
    }

    public function test_signup_accepts_valid_turnstile_token_when_enabled(): void
    {
        $this->app->instance(BotVerifier::class, new class implements BotVerifier
        {
            public function verify(string $token, ?string $remoteIp = null): bool
            {
                return $token === 'valid-token';
            }
        });

        $response = $this->postJson('/api/v1/signup', [
            'email' => 'turnstile-ok@example.com',
            'password' => 'securepassword12',
            'store_name' => 'Turnstile OK Shop',
            'plan_slug' => 'starter',
            'cf-turnstile-response' => 'valid-token',
        ]);

        $response->assertAccepted();
    }

    public function test_checkout_create_rejects_invalid_turnstile_when_enabled(): void
    {
        $this->app->instance(BotVerifier::class, new class implements BotVerifier
        {
            public function verify(string $token, ?string $remoteIp = null): bool
            {
                return false;
            }
        });

        $tenant = \Platform\Tenancy\Models\Tenant::query()->create([
            'slug' => 'turnstile-'.Str::random(6),
            'name' => 'Turnstile Tenant',
            'status' => 'active',
            'country' => 'NG',
        ]);

        $response = $this->postJson('/api/v1/commerce/checkout/sessions', [
            'cart_id' => (string) Str::uuid(),
            'cf-turnstile-response' => 'bad-token',
        ], $this->tenantMoneyHeaders($tenant->id));

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['cf-turnstile-response']);
    }
}
