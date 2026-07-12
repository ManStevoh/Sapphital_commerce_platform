<?php

declare(strict_types=1);

namespace Modules\Commerce\Catalog\Services;

use Illuminate\Support\Facades\File;
use InvalidArgumentException;
use Platform\Tenancy\Models\Tenant;

final class ThemeResolver
{
    public const DEFAULT_THEME_ID = 'scp-dawn';

    /**
     * @return array<string, mixed>
     */
    /**
     * @return array<int, array<string, mixed>>
     */
    public function listAvailableThemes(): array
    {
        $themesPath = base_path('Themes');

        if (! File::isDirectory($themesPath)) {
            return [];
        }

        $themes = [];

        foreach (File::directories($themesPath) as $directory) {
            $manifestPath = $directory.'/theme.json';

            if (! File::exists($manifestPath)) {
                continue;
            }

            $manifest = $this->loadJsonFile($manifestPath);

            $themes[] = [
                'id' => $manifest['id'] ?? basename($directory),
                'name' => $manifest['name'] ?? basename($directory),
                'version' => $manifest['version'] ?? '0.0.0',
                'description' => $manifest['description'] ?? '',
                'market' => $manifest['market'] ?? 'NG',
                'colors' => $manifest['colors'] ?? [],
            ];
        }

        usort($themes, fn (array $a, array $b): int => strcmp((string) $a['id'], (string) $b['id']));

        return $themes;
    }

    public function themeExists(string $themeId): bool
    {
        $themePath = $this->themePath($themeId);

        return File::isDirectory($themePath) && File::exists($themePath.'/theme.json');
    }

    public function resolveForTenant(Tenant $tenant): array
    {
        $tenantSettings = $tenant->settings ?? [];
        $themeId = is_array($tenantSettings) && isset($tenantSettings['theme_id']) && is_string($tenantSettings['theme_id'])
            ? $tenantSettings['theme_id']
            : self::DEFAULT_THEME_ID;

        if (! $this->themeExists($themeId)) {
            throw new InvalidArgumentException("Theme package not found: {$themeId}");
        }

        $themePath = $this->themePath($themeId);

        $manifest = $this->loadJsonFile($themePath.'/theme.json');
        $defaults = $this->loadJsonFile($themePath.'/defaults.json');

        $merchantOverrides = is_array($tenantSettings['theme_settings'] ?? null)
            ? $tenantSettings['theme_settings']
            : [];

        $settings = array_merge($defaults, $merchantOverrides);

        return [
            'theme_id' => $themeId,
            'id' => $manifest['id'] ?? $themeId,
            'name' => $manifest['name'] ?? $themeId,
            'version' => $manifest['version'] ?? '0.0.0',
            'schema_version' => $manifest['schema_version'] ?? '1.0',
            'market' => $manifest['market'] ?? 'NG',
            'templates' => $manifest['templates'] ?? [],
            'colors' => $manifest['colors'] ?? [],
            'settings' => $settings,
        ];
    }

    private function themePath(string $themeId): string
    {
        return base_path('Themes/'.$themeId);
    }

    /**
     * @return array<string, mixed>
     */
    private function loadJsonFile(string $path): array
    {
        if (! File::exists($path)) {
            throw new InvalidArgumentException("Theme file not found: {$path}");
        }

        $contents = File::get($path);
        $decoded = json_decode($contents, true);

        if (! is_array($decoded)) {
            throw new InvalidArgumentException("Invalid JSON in theme file: {$path}");
        }

        return $decoded;
    }
}
