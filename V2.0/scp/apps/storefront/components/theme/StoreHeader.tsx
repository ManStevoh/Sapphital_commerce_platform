import Link from 'next/link';
import type { ThemeConfig } from '@/lib/api';

interface StoreHeaderProps {
  storeName: string;
  tenantSlug?: string | null;
  theme: ThemeConfig | null;
}

export function StoreHeader({ storeName, tenantSlug, theme }: StoreHeaderProps) {
  const primary = theme?.settings.primary_color ?? theme?.colors.primary ?? 'var(--color-brand)';

  return (
    <header
      style={{
        borderBottom: '1px solid var(--color-border)',
        padding: '1rem 0',
        marginBottom: '1.5rem',
      }}
    >
      <div
        style={{
          display: 'flex',
          justifyContent: 'space-between',
          alignItems: 'center',
          gap: '1rem',
          flexWrap: 'wrap',
        }}
      >
        <div>
          <h1 style={{ margin: 0, fontSize: '1.5rem', color: primary }}>{storeName}</h1>
          {tenantSlug && (
            <p style={{ margin: '0.25rem 0 0', color: 'var(--color-text-muted)', fontSize: '0.875rem' }}>
              {tenantSlug}.shops.sapphital.test
            </p>
          )}
        </div>
        <nav style={{ display: 'flex', gap: '1rem', fontSize: '0.875rem' }}>
          <Link href="/">Shop</Link>
          <Link href="/cart">Cart</Link>
        </nav>
      </div>
    </header>
  );
}
