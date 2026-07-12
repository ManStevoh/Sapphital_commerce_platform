import type { ThemeConfig } from '@/lib/api';

interface HeroSectionProps {
  storeName: string;
  theme: ThemeConfig | null;
}

export function HeroSection({ storeName, theme }: HeroSectionProps) {
  const primary = theme?.settings.primary_color ?? theme?.colors.primary ?? 'var(--color-brand)';

  return (
    <section
      style={{
        background: `linear-gradient(135deg, ${primary}15, var(--color-bg-subtle))`,
        borderRadius: 12,
        padding: '2rem 1.5rem',
        marginBottom: '2rem',
      }}
    >
      <p style={{ margin: 0, fontSize: '0.875rem', color: 'var(--color-text-secondary)' }}>
        Welcome to
      </p>
      <h2 style={{ margin: '0.25rem 0 0.75rem', fontSize: '1.75rem' }}>{storeName}</h2>
      <p style={{ margin: 0, color: 'var(--color-text-secondary)', maxWidth: 480 }}>
        Shop quality products with secure Paystack checkout — cards, bank transfer, and USSD.
      </p>
    </section>
  );
}
