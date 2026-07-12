'use client';

import Link from 'next/link';
import type { ReactNode } from 'react';
import { tokens } from './tokens';
import { Button } from './Button';

export interface AdminNavItem {
  href: string;
  label: string;
}

export interface AdminShellProps {
  title: string;
  subtitle?: string;
  nav: AdminNavItem[];
  activeHref?: string;
  onSignOut?: () => void;
  children: ReactNode;
}

export function AdminShell({
  title,
  subtitle,
  nav,
  activeHref,
  onSignOut,
  children,
}: AdminShellProps) {
  return (
    <div
      style={{
        minHeight: '100vh',
        backgroundColor: tokens.color.bgSubtle,
        fontFamily: tokens.font.family,
        color: tokens.color.text,
      }}
    >
      <header
        style={{
          backgroundColor: tokens.color.surface,
          borderBottom: `1px solid ${tokens.color.border}`,
          padding: `${tokens.space[2]}px ${tokens.space[3]}px`,
        }}
      >
        <div
          style={{
            maxWidth: 960,
            margin: '0 auto',
            display: 'flex',
            justifyContent: 'space-between',
            alignItems: 'center',
            gap: tokens.space[2],
            flexWrap: 'wrap',
          }}
        >
          <div>
            <p
              style={{
                margin: 0,
                fontSize: tokens.font.sizeSm,
                color: tokens.color.textMuted,
                fontWeight: tokens.font.weightMedium,
              }}
            >
              SAPPHITAL Merchant OS
            </p>
            <nav aria-label="Admin navigation" style={{ display: 'flex', gap: tokens.space[2] }}>
              {nav.map((item) => {
                const active = activeHref === item.href;
                return (
                  <Link
                    key={item.href}
                    href={item.href}
                    style={{
                      color: active ? tokens.color.brand : tokens.color.textSecondary,
                      fontWeight: active ? tokens.font.weightSemibold : tokens.font.weightMedium,
                      textDecoration: active ? 'underline' : 'none',
                      fontSize: tokens.font.sizeSm,
                    }}
                  >
                    {item.label}
                  </Link>
                );
              })}
            </nav>
          </div>
          {onSignOut && (
            <Button variant="secondary" onClick={onSignOut}>
              Sign out
            </Button>
          )}
        </div>
      </header>

      <main
        style={{
          maxWidth: 960,
          margin: '0 auto',
          padding: `${tokens.space[4]}px ${tokens.space[3]}px`,
        }}
      >
        <header style={{ marginBottom: tokens.space[3] }}>
          <h1 style={{ margin: 0, fontSize: tokens.font.sizeXl }}>{title}</h1>
          {subtitle && (
            <p style={{ margin: `${tokens.space[1]}px 0 0`, color: tokens.color.textSecondary }}>
              {subtitle}
            </p>
          )}
        </header>
        {children}
      </main>
    </div>
  );
}
