/** SAPPHITAL Design System — semantic tokens (Vol 4 Ch. 02). */
export const tokens = {
  color: {
    brand: '#006644',
    brandHover: '#005538',
    brandSubtle: '#ecfdf5',
    bg: '#ffffff',
    bgSubtle: '#f8fafc',
    surface: '#ffffff',
    text: '#0f172a',
    textSecondary: '#475569',
    textMuted: '#64748b',
    textInverse: '#ffffff',
    border: '#e2e8f0',
    borderStrong: '#cbd5e1',
    divider: '#f1f5f9',
    success: '#16a34a',
    warning: '#d97706',
    error: '#dc2626',
    errorSubtle: '#fef2f2',
    info: '#2563eb',
  },
  space: {
    0: 0,
    0.5: 4,
    1: 8,
    2: 16,
    3: 24,
    4: 32,
    5: 40,
    6: 48,
  },
  radius: {
    sm: 4,
    md: 8,
    lg: 12,
  },
  font: {
    family: 'system-ui, -apple-system, Segoe UI, Roboto, sans-serif',
    sizeSm: '0.875rem',
    sizeBase: '1rem',
    sizeLg: '1.25rem',
    sizeXl: '1.5rem',
    weightMedium: 500,
    weightSemibold: 600,
  },
  shadow: {
    sm: '0 1px 2px rgba(15, 23, 42, 0.06)',
    md: '0 4px 12px rgba(15, 23, 42, 0.08)',
  },
} as const;

export type Tokens = typeof tokens;
