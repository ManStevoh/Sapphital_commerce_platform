import type { Metadata } from 'next';
import { headers } from 'next/headers';
import '@sapphital/scp-ui/tokens.css';
import { loadStorefrontTheme, themeStyle } from '@/lib/theme-loader';

export const metadata: Metadata = {
  title: 'SAPPHITAL Storefront',
  description: 'Tenant storefront runtime — SAPPHITAL SCP',
};

export default async function RootLayout({
  children,
}: Readonly<{
  children: React.ReactNode;
}>) {
  const requestHeaders = await headers();
  const tenantSlug = requestHeaders.get('x-tenant-slug');
  const tenantId = requestHeaders.get('x-tenant-id');
  const themeBundle = await loadStorefrontTheme();

  return (
    <html
      lang="en"
      data-tenant-slug={tenantSlug ?? undefined}
      data-tenant-id={tenantId ?? undefined}
    >
      <body
        style={{
          margin: 0,
          ...(themeBundle ? themeStyle(themeBundle.cssVariables) : {}),
        }}
      >
        {children}
      </body>
    </html>
  );
}
