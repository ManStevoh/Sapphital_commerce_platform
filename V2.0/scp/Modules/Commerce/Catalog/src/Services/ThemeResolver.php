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
                'vertical' => $manifest['vertical'] ?? null,
                'colors' => $manifest['colors'] ?? [],
                'sections' => $manifest['sections'] ?? [],
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
            'vertical' => $manifest['vertical'] ?? null,
            'templates' => $manifest['templates'] ?? [],
            'sections' => $manifest['sections'] ?? [],
            'colors' => $manifest['colors'] ?? [],
            'settings' => $settings,
        ];
    }

    /**
     * Switch tenant theme while retaining merchant-owned settings.
     *
     * @return array{theme: array<string, mixed>, portability: array<string, mixed>}
     */
    public function applyTheme(Tenant $tenant, string $themeId): array
    {
        if (! $this->themeExists($themeId)) {
            throw new InvalidArgumentException("Theme package not found: {$themeId}");
        }

        $settings = is_array($tenant->settings) ? $tenant->settings : [];
        $previousThemeId = is_string($settings['theme_id'] ?? null)
            ? $settings['theme_id']
            : self::DEFAULT_THEME_ID;
        $themeSettings = is_array($settings['theme_settings'] ?? null)
            ? $settings['theme_settings']
            : [];

        $settings['theme_id'] = $themeId;
        $tenant->update(['settings' => $settings]);

        $fresh = $tenant->fresh();
        if ($fresh === null) {
            throw new InvalidArgumentException('Tenant not found after theme apply.');
        }

        $theme = $this->resolveForTenant($fresh);

        return [
            'theme' => $theme,
            'portability' => [
                'from_theme_id' => $previousThemeId,
                'to_theme_id' => $themeId,
                'retained_settings' => array_keys($themeSettings),
                'retained_content' => [
                    'cms_pages' => true,
                    'products' => true,
                    'collections' => true,
                    'navigation' => true,
                    'blog_posts' => true,
                ],
                'dropped_section_types' => $this->sectionDiff($previousThemeId, $themeId),
                'message' => 'Merchant-owned catalog, CMS, and brand settings were retained. Layout sections follow the new theme package.',
            ],
        ];
    }

    /**
     * @return list<string>
     */
    private function sectionDiff(string $fromThemeId, string $toThemeId): array
    {
        if (! $this->themeExists($fromThemeId) || ! $this->themeExists($toThemeId)) {
            return [];
        }

        $from = $this->loadJsonFile($this->themePath($fromThemeId).'/theme.json');
        $to = $this->loadJsonFile($this->themePath($toThemeId).'/theme.json');
        $fromSections = is_array($from['sections'] ?? null) ? $from['sections'] : [];
        $toSections = is_array($to['sections'] ?? null) ? $to['sections'] : [];

        /** @var list<string> $diff */
        $diff = array_values(array_diff(
            array_map('strval', $fromSections),
            array_map('strval', $toSections),
        ));

        return $diff;
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
