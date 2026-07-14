'use client';

import { FormEvent, useEffect, useState } from 'react';
import { useRouter } from 'next/navigation';
import { AdminShell, Alert, Button, Card, Input } from '@sapphital/scp-ui';
import {
  applyTheme,
  clearAuth,
  fetchTheme,
  fetchThemeCatalog,
  getStoredTenantId,
  getStoredToken,
  previewTheme,
  updateThemeSettings,
  type ThemeCatalogEntry,
  type ThemePortabilityReport,
} from '@/lib/api';
import { adminNav } from '@/lib/nav';

export default function ThemeSettingsPage() {
  const router = useRouter();
  const [primaryColor, setPrimaryColor] = useState('#006644');
  const [fontHeading, setFontHeading] = useState('Inter');
  const [logoUrl, setLogoUrl] = useState('');
  const [themeName, setThemeName] = useState('');
  const [themeId, setThemeId] = useState('');
  const [catalog, setCatalog] = useState<ThemeCatalogEntry[]>([]);
  const [previewSections, setPreviewSections] = useState<string[]>([]);
  const [portability, setPortability] = useState<ThemePortabilityReport | null>(null);
  const [loading, setLoading] = useState(true);
  const [submitting, setSubmitting] = useState(false);
  const [workingThemeId, setWorkingThemeId] = useState<string | null>(null);
  const [error, setError] = useState<string | null>(null);
  const [success, setSuccess] = useState<string | null>(null);

  useEffect(() => {
    const token = getStoredToken();
    const tenantId = getStoredTenantId();

    if (!token || !tenantId) {
      router.replace('/login');
      return;
    }

    Promise.all([fetchTheme(tenantId), fetchThemeCatalog()])
      .then(([theme, themes]) => {
        setThemeName(theme.name);
        setThemeId(theme.theme_id);
        setPrimaryColor(theme.settings.primary_color);
        setFontHeading(theme.settings.font_heading);
        setLogoUrl(theme.settings.logo_url ?? '');
        setPreviewSections(theme.sections ?? []);
        setCatalog(themes);
      })
      .catch((err) => {
        setError(err instanceof Error ? err.message : 'Failed to load theme.');
      })
      .finally(() => {
        setLoading(false);
      });
  }, [router]);

  async function handleSubmit(event: FormEvent<HTMLFormElement>) {
    event.preventDefault();
    const tenantId = getStoredTenantId();

    if (!tenantId) {
      return;
    }

    setSubmitting(true);
    setError(null);
    setSuccess(null);

    try {
      await updateThemeSettings(tenantId, {
        primary_color: primaryColor,
        font_heading: fontHeading,
        logo_url: logoUrl.trim() || null,
      });
      setSuccess('Theme settings saved. Storefront will reflect changes on next visit.');
    } catch (err) {
      setError(err instanceof Error ? err.message : 'Failed to save theme settings.');
    } finally {
      setSubmitting(false);
    }
  }

  async function handlePreview(id: string) {
    const tenantId = getStoredTenantId();

    if (!tenantId) {
      return;
    }

    setWorkingThemeId(id);
    setError(null);

    try {
      const preview = await previewTheme(tenantId, id);
      setPreviewSections(preview.sections ?? []);
      setSuccess(`Preview ready for ${preview.name} (not applied).`);
    } catch (err) {
      setError(err instanceof Error ? err.message : 'Preview failed.');
    } finally {
      setWorkingThemeId(null);
    }
  }

  async function handleApply(id: string) {
    const tenantId = getStoredTenantId();

    if (!tenantId) {
      return;
    }

    setWorkingThemeId(id);
    setError(null);
    setSuccess(null);

    try {
      const result = await applyTheme(tenantId, id);
      setThemeId(result.theme.theme_id);
      setThemeName(result.theme.name);
      setPrimaryColor(result.theme.settings.primary_color);
      setFontHeading(result.theme.settings.font_heading);
      setLogoUrl(result.theme.settings.logo_url ?? '');
      setPreviewSections(result.theme.sections ?? []);
      setPortability(result.portability);
      setSuccess(`Applied ${result.theme.name}.`);
    } catch (err) {
      setError(err instanceof Error ? err.message : 'Failed to apply theme.');
    } finally {
      setWorkingThemeId(null);
    }
  }

  function handleLogout() {
    clearAuth();
    router.push('/login');
  }

  if (loading) {
    return (
      <AdminShell title="Store theme" subtitle="Loading…" nav={adminNav} activeHref="/online-store/theme">
        <p>Loading theme…</p>
      </AdminShell>
    );
  }

  return (
    <AdminShell
      title="Store theme"
      subtitle={themeName ? `Active theme: ${themeName}` : undefined}
      nav={adminNav}
      activeHref="/online-store/theme"
      onSignOut={handleLogout}
    >
      <Card title="Theme picker">
        <p style={{ marginTop: 0, color: 'var(--color-text-secondary)' }}>
          Choose a Phase 1 retail theme or a Phase 2 vertical theme. Preview before apply.
        </p>
        <ul style={{ listStyle: 'none', padding: 0, margin: 0, display: 'grid', gap: 12 }}>
          {catalog.map((entry) => (
            <li
              key={entry.id}
              style={{
                border: '1px solid var(--color-border)',
                padding: 12,
                display: 'grid',
                gap: 8,
              }}
            >
              <div style={{ display: 'flex', justifyContent: 'space-between', gap: 12 }}>
                <div>
                  <strong>{entry.name}</strong>
                  {entry.id === themeId ? ' (active)' : ''}
                  <div style={{ color: 'var(--color-text-secondary)', fontSize: 14 }}>
                    {entry.vertical ? `Vertical: ${entry.vertical}` : 'General retail'} · {entry.id}
                  </div>
                </div>
                <span
                  aria-hidden
                  style={{
                    width: 28,
                    height: 28,
                    background: entry.colors.primary ?? '#ccc',
                    borderRadius: 4,
                  }}
                />
              </div>
              <p style={{ margin: 0, fontSize: 14 }}>{entry.description}</p>
              <div style={{ display: 'flex', gap: 8, flexWrap: 'wrap' }}>
                <Button
                  type="button"
                  disabled={workingThemeId !== null}
                  onClick={() => handlePreview(entry.id)}
                >
                  {workingThemeId === entry.id ? 'Working…' : 'Preview'}
                </Button>
                <Button
                  type="button"
                  disabled={workingThemeId !== null || entry.id === themeId}
                  onClick={() => handleApply(entry.id)}
                >
                  Apply
                </Button>
              </div>
            </li>
          ))}
        </ul>
        {previewSections.length > 0 && (
          <p style={{ fontSize: 14, color: 'var(--color-text-secondary)' }}>
            Sections: {previewSections.join(', ')}
          </p>
        )}
      </Card>

      {portability && (
        <Card title="Portability report">
          <p style={{ marginTop: 0 }}>{portability.message}</p>
          <p style={{ fontSize: 14 }}>
            {portability.from_theme_id} → {portability.to_theme_id}
          </p>
          <p style={{ fontSize: 14 }}>
            Retained settings: {portability.retained_settings.join(', ') || 'none'}
          </p>
          {portability.dropped_section_types.length > 0 && (
            <p style={{ fontSize: 14 }}>
              Sections not in new theme: {portability.dropped_section_types.join(', ')}
            </p>
          )}
        </Card>
      )}

      <Card title="Brand settings">
        <form onSubmit={handleSubmit}>
          <Input
            label="Primary color"
            type="color"
            value={primaryColor}
            onChange={(e) => setPrimaryColor(e.target.value)}
          />
          <Input
            label="Heading font"
            value={fontHeading}
            onChange={(e) => setFontHeading(e.target.value)}
          />
          <Input
            label="Logo URL (optional)"
            type="url"
            value={logoUrl}
            onChange={(e) => setLogoUrl(e.target.value)}
            placeholder="https://..."
          />

          {error && <Alert>{error}</Alert>}
          {success && <Alert variant="success">{success}</Alert>}

          <Button type="submit" disabled={submitting}>
            {submitting ? 'Saving…' : 'Save brand settings'}
          </Button>
        </form>
      </Card>
    </AdminShell>
  );
}
