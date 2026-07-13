<?php

declare(strict_types=1);

namespace Tests\Security;

use Illuminate\Support\Facades\Route;
use Tests\TestCase;

final class AuthzManifestCompletenessTest extends TestCase
{
    public function test_manifest_covers_all_named_api_routes(): void
    {
        $manifest = array_keys(config('authz-routes', []));

        $registered = collect(Route::getRoutes())
            ->filter(fn ($route) => $route->getName() !== null)
            ->filter(fn ($route) => str_starts_with($route->uri(), 'api/') || str_starts_with($route->uri(), 'v1/'))
            ->map(fn ($route) => $route->getName())
            ->unique()
            ->sort()
            ->values()
            ->all();

        $missing = array_values(array_diff($registered, $manifest));
        $stale = array_values(array_diff($manifest, $registered));

        $this->assertSame(
            [],
            $missing,
            'Add missing routes to config/authz-routes.php: '.implode(', ', $missing),
        );

        $this->assertSame(
            [],
            $stale,
            'Remove stale routes from config/authz-routes.php: '.implode(', ', $stale),
        );
    }
}
