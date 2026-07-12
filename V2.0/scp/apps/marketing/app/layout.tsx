import type { Metadata } from 'next';
import '@sapphital/scp-ui/tokens.css';

export const metadata: Metadata = {
  title: 'SAPPHITAL',
  description: 'Public website + signup — SAPPHITAL SCP',
};

export default function RootLayout({
  children,
}: Readonly<{
  children: React.ReactNode;
}>) {
  return (
    <html lang="en">
      <body style={{ margin: 0 }}>{children}</body>
    </html>
  );
}
