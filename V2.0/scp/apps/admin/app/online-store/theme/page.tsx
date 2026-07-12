'use client';

import { FormEvent, useEffect, useState } from 'react';
import { useRouter } from 'next/navigation';
import { AdminShell, Alert, Button, Card, Input } from '@sapphital/scp-ui';
import {
  clearAuth,
  fetchTheme,
  getStoredTenantId,
  getStoredToken,
  updateThemeSettings,
} from '@/lib/api';
import { adminNav } from '@/lib/nav';

export default function ThemeSettingsPage() {
  const router = useRouter();
  const [primaryColor, setPrimaryColor] = useState('#006644');
  const [fontHeading, setFontHeading] = useState('Inter');
  const [logoUrl, setLogoUrl] = useState('');
  const [themeName, setThemeName] = useState('');
  const [loading, setLoading] = useState(true);
  const [submitting, setSubmitting] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [success, setSuccess] = useState<string | null>(null);

  useEffect(() => {
    const token = getStoredToken();
    const tenantId = getStoredTenantId();

    if (!token || !tenantId) {
      router.replace('/login');
      return;
    }

    fetchTheme(tenantId)
      .then((theme) => {
        setThemeName(theme.name);
        setPrimaryColor(theme.settings.primary_color);
        setFontHeading(theme.settings.font_heading);
        setLogoUrl(theme.settings.logo_url ?? '');
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
            {submitting ? 'Saving…' : 'Save theme'}
          </Button>
        </form>
      </Card>
    </AdminShell>
  );
}
