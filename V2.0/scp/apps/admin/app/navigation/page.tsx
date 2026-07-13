'use client';

import { FormEvent, useEffect, useState } from 'react';
import { useRouter } from 'next/navigation';
import { AdminShell, Alert, Button, Card, Input } from '@sapphital/scp-ui';
import {
  clearAuth,
  fetchCmsNavigation,
  getStoredTenantId,
  getStoredToken,
  updateCmsNavigation,
  type CmsNavLink,
} from '@/lib/api';
import { adminNav } from '@/lib/nav';

type MenuLocation = 'header' | 'footer';

interface LinkRow extends CmsNavLink {
  key: string;
}

function emptyLink(): LinkRow {
  return { key: crypto.randomUUID(), label: '', href: '', open_in_new_tab: false };
}

export default function NavigationPage() {
  const router = useRouter();
  const [location, setLocation] = useState<MenuLocation>('header');
  const [headerLinks, setHeaderLinks] = useState<LinkRow[]>([emptyLink()]);
  const [footerLinks, setFooterLinks] = useState<LinkRow[]>([emptyLink()]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const [success, setSuccess] = useState<string | null>(null);
  const [working, setWorking] = useState(false);

  useEffect(() => {
    const token = getStoredToken();
    const tenantId = getStoredTenantId();

    if (!token || !tenantId) {
      router.replace('/login');
      return;
    }

    Promise.all([
      fetchCmsNavigation(tenantId, 'header'),
      fetchCmsNavigation(tenantId, 'footer'),
    ])
      .then(([header, footer]) => {
        setHeaderLinks(
          header.links.length > 0
            ? header.links.map((link) => ({ ...link, key: crypto.randomUUID() }))
            : [emptyLink()],
        );
        setFooterLinks(
          footer.links.length > 0
            ? footer.links.map((link) => ({ ...link, key: crypto.randomUUID() }))
            : [emptyLink()],
        );
      })
      .catch((err) => {
        setError(err instanceof Error ? err.message : 'Failed to load navigation.');
      })
      .finally(() => {
        setLoading(false);
      });
  }, [router]);

  const activeLinks = location === 'header' ? headerLinks : footerLinks;
  const setActiveLinks = location === 'header' ? setHeaderLinks : setFooterLinks;

  function updateLink(key: string, field: keyof CmsNavLink, value: string | boolean) {
    setActiveLinks((current) =>
      current.map((link) => (link.key === key ? { ...link, [field]: value } : link)),
    );
  }

  function addLink() {
    setActiveLinks((current) => [...current, emptyLink()]);
  }

  function removeLink(key: string) {
    setActiveLinks((current) =>
      current.length <= 1 ? current : current.filter((link) => link.key !== key),
    );
  }

  async function handleSave(event: FormEvent<HTMLFormElement>) {
    event.preventDefault();
    const tenantId = getStoredTenantId();

    if (!tenantId) {
      return;
    }

    const links = activeLinks
      .filter((link) => link.label.trim() && link.href.trim())
      .map(({ label, href, open_in_new_tab }) => ({
        label: label.trim(),
        href: href.trim(),
        open_in_new_tab: Boolean(open_in_new_tab),
      }));

    setWorking(true);
    setError(null);
    setSuccess(null);

    try {
      const saved = await updateCmsNavigation(tenantId, location, links);
      const rows =
        saved.links.length > 0
          ? saved.links.map((link) => ({ ...link, key: crypto.randomUUID() }))
          : [emptyLink()];
      if (location === 'header') {
        setHeaderLinks(rows);
      } else {
        setFooterLinks(rows);
      }
      setSuccess(`${location} menu saved.`);
    } catch (err) {
      setError(err instanceof Error ? err.message : 'Failed to save navigation.');
    } finally {
      setWorking(false);
    }
  }

  function handleLogout() {
    clearAuth();
    router.push('/login');
  }

  if (loading) {
    return (
      <AdminShell title="Navigation" subtitle="Loading…" nav={adminNav} activeHref="/navigation">
        <p>Loading menus…</p>
      </AdminShell>
    );
  }

  return (
    <AdminShell
      title="Navigation"
      subtitle="Header and footer links"
      nav={adminNav}
      activeHref="/navigation"
      onSignOut={handleLogout}
    >
      {error && <Alert>{error}</Alert>}
      {success && <Alert variant="success">{success}</Alert>}

      <div style={{ display: 'flex', gap: '0.5rem', marginBottom: '1rem' }}>
        <Button
          type="button"
          variant={location === 'header' ? undefined : 'secondary'}
          onClick={() => setLocation('header')}
        >
          Header
        </Button>
        <Button
          type="button"
          variant={location === 'footer' ? undefined : 'secondary'}
          onClick={() => setLocation('footer')}
        >
          Footer
        </Button>
      </div>

      <Card title={`${location} menu`}>
        <form onSubmit={handleSave}>
          {activeLinks.map((link) => (
            <div
              key={link.key}
              style={{
                display: 'grid',
                gridTemplateColumns: '1fr 1fr auto',
                gap: '0.5rem',
                marginBottom: '0.75rem',
                alignItems: 'end',
              }}
            >
              <Input
                label="Label"
                value={link.label}
                onChange={(e) => updateLink(link.key, 'label', e.target.value)}
              />
              <Input
                label="URL"
                value={link.href}
                onChange={(e) => updateLink(link.key, 'href', e.target.value)}
                placeholder="/about or https://…"
              />
              <Button type="button" variant="secondary" onClick={() => removeLink(link.key)}>
                Remove
              </Button>
            </div>
          ))}

          <div style={{ display: 'flex', gap: '0.5rem', marginTop: '1rem' }}>
            <Button type="button" variant="secondary" onClick={addLink}>
              Add link
            </Button>
            <Button type="submit" disabled={working}>
              {working ? 'Saving…' : 'Save menu'}
            </Button>
          </div>
        </form>
      </Card>
    </AdminShell>
  );
}
