import type { ReactNode } from 'react';
import { tokens } from './tokens';

export interface AlertProps {
  variant?: 'error' | 'success' | 'info' | 'warning';
  children: ReactNode;
}

const variantStyles = {
  error: {
    backgroundColor: tokens.color.errorSubtle,
    color: tokens.color.error,
    borderColor: tokens.color.error,
  },
  success: {
    backgroundColor: '#f0fdf4',
    color: tokens.color.success,
    borderColor: tokens.color.success,
  },
  info: {
    backgroundColor: '#eff6ff',
    color: tokens.color.info,
    borderColor: tokens.color.info,
  },
  warning: {
    backgroundColor: '#fffbeb',
    color: tokens.color.warning,
    borderColor: tokens.color.warning,
  },
} as const;

export function Alert({ variant = 'error', children }: AlertProps) {
  const style = variantStyles[variant];

  return (
    <p
      role="alert"
      style={{
        margin: `${tokens.space[2]}px 0`,
        padding: tokens.space[2],
        borderRadius: tokens.radius.sm,
        border: `1px solid ${style.borderColor}`,
        backgroundColor: style.backgroundColor,
        color: style.color,
      }}
    >
      {children}
    </p>
  );
}
