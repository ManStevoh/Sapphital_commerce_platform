import type { CSSProperties } from 'react';
import { headers } from 'next/headers';
import { fetchTheme, type ThemeConfig } from '@/lib/api';

export interface ResolvedTheme {
  config: ThemeConfig;
  cssVariables: Record<string, string>;
}

export async function loadStorefrontTheme(): Promise<ResolvedTheme | null> {
  const requestHeaders = await headers();
  const tenantId = requestHeaders.get('x-tenant-id');

  if (!tenantId) {
    return null;
  }

  try {
    const config = await fetchTheme(tenantId);
    const primary =
      config.settings.primary_color ?? config.colors.primary ?? '#006644';
    const background =
      config.colors.background ?? '#ffffff';
    const foreground =
      config.colors.foreground ?? '#0f172a';

    return {
      config,
      cssVariables: {
        '--theme-primary': primary,
        '--theme-secondary': config.colors.secondary ?? primary,
        '--theme-accent': config.colors.accent ?? '#E9C46A',
        '--theme-background': background,
        '--theme-foreground': foreground,
        '--color-brand': primary,
        '--color-bg': background,
        '--color-text': foreground,
      },
    };
  } catch {
    return null;
  }
}

export function themeStyle(
  variables: Record<string, string>,
): CSSProperties {
  return variables as CSSProperties;
}
