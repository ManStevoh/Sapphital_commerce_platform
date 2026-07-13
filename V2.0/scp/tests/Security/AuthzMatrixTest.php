<?php

declare(strict_types=1);

namespace Tests\Security;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use Platform\Tenancy\Models\Tenant;
use Tests\Feature\PlatformTestCase;

final class AuthzMatrixTest extends PlatformTestCase
{
    /**
     * @return array<string, array{0: string, 1: array{method: string, uri: string, archetype: string}}>
     */
    public static function protectedRoutesProvider(): array
    {
        /** @var array<string, array{method: string, uri: string, archetype: string}> $routes */
        $routes = require dirname(__DIR__, 2).'/config/authz-routes.php';
        $cases = [];

        foreach ($routes as $name => $route) {
            if (($route['archetype'] ?? 'public') === 'public') {
                continue;
            }

            $cases[$name] = [$name, $route];
        }

        return $cases;
    }

    /**
     * @dataProvider protectedRoutesProvider
     *
     * @param  array{method: string, uri: string, archetype: string}  $route
     */
    public function test_protected_route_denies_anonymous_access(string $name, array $route): void
    {
        $headers = $this->headersForRoute($name);

        $response = $this->json(
            $route['method'],
            $route['uri'],
            $this->sampleBody($route['method']),
            $headers,
        );

        $expectedStatuses = match ($route['archetype']) {
            'sanctum', 'merchant' => [401],
            'tenant' => [403],
            'platform' => [401, 403],
            default => [401],
        };

        $this->assertContains(
            $response->status(),
            $expectedStatuses,
            "Route {$name} should deny anonymous access.",
        );
    }

    public function test_merchant_cannot_access_platform_routes(): void
    {
        $tenant = $this->createTenantForAuthz();
        $merchant = $this->createMerchantForTenant($tenant);
        $token = $merchant->createToken('authz')->plainTextToken;

        foreach (config('authz-routes', []) as $name => $route) {
            if (($route['archetype'] ?? '') !== 'platform') {
                continue;
            }

            $response = $this->json(
                $route['method'],
                $route['uri'],
                $this->sampleBody($route['method']),
                $this->merchantAuthHeaders($tenant->id, $token),
            );

            $response->assertStatus(403, "Route {$name} should reject merchant token.");
        }
    }

    public function test_public_routes_allow_anonymous_get(): void
    {
        foreach (config('authz-routes', []) as $name => $route) {
            if (($route['archetype'] ?? '') !== 'public' || $route['method'] !== 'GET') {
                continue;
            }

            $response = $this->getJson($route['uri']);

            $this->assertTrue(
                $response->status() < 500,
                "Route {$name} should not 5xx for anonymous GET.",
            );
        }
    }

    /**
     * @return array<string, string>
     */
    private function headersForRoute(string $name): array
    {
        if (in_array($name, [
            'checkout.sessions.store',
            'financial-services.payments.initialize',
            'financial-services.payments.verify',
            'orders.refund',
            'returns.approve',
            'returns.receive',
            'billing.subscriptions.initialize-payment',
        ], true)) {
            return $this->idempotencyHeaders();
        }

        return [];
    }

    /**
     * @return array<string, mixed>
     */
    private function sampleBody(string $method): array
    {
        if (! in_array($method, ['POST', 'PUT', 'PATCH'], true)) {
            return [];
        }

        return match ($method) {
            'POST' => [
                'email' => 'authz@example.com',
                'password' => 'securepassword12',
                'store_name' => 'Authz Store',
                'plan_slug' => 'starter',
                'cart_id' => (string) Str::uuid(),
                'checkout_session_id' => (string) Str::uuid(),
                'reference' => 'authz_ref',
                'product_id' => (string) Str::uuid(),
                'quantity' => 1,
                'order_id' => (string) Str::uuid(),
            ],
            'PUT', 'PATCH' => ['status' => 'active', 'name' => 'Updated'],
            default => [],
        };
    }

    private function createTenantForAuthz(): Tenant
    {
        return Tenant::query()->create([
            'slug' => 'authz-'.Str::random(6),
            'name' => 'Authz Tenant',
            'status' => 'active',
            'country' => 'NG',
        ]);
    }
}
