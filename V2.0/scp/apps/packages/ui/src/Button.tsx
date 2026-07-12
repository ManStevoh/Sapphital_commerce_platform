import type { ButtonHTMLAttributes, CSSProperties, ReactNode } from 'react';
import { tokens } from './tokens';

export interface ButtonProps extends ButtonHTMLAttributes<HTMLButtonElement> {
  children: ReactNode;
  variant?: 'primary' | 'secondary' | 'danger';
}

export function Button({
  children,
  variant = 'primary',
  style,
  ...props
}: ButtonProps) {
  const variants: Record<NonNullable<ButtonProps['variant']>, CSSProperties> = {
    primary: {
      backgroundColor: tokens.color.brand,
      color: tokens.color.textInverse,
      borderColor: tokens.color.brand,
    },
    secondary: {
      backgroundColor: tokens.color.bgSubtle,
      color: tokens.color.text,
      borderColor: tokens.color.border,
    },
    danger: {
      backgroundColor: tokens.color.errorSubtle,
      color: tokens.color.error,
      borderColor: tokens.color.error,
    },
  };

  const baseStyle: CSSProperties = {
    padding: `${tokens.space[1]}px ${tokens.space[2]}px`,
    borderRadius: tokens.radius.sm,
    border: '1px solid transparent',
    cursor: props.disabled ? 'not-allowed' : 'pointer',
    fontWeight: tokens.font.weightSemibold,
    fontSize: tokens.font.sizeSm,
    opacity: props.disabled ? 0.6 : 1,
    ...variants[variant],
    ...style,
  };

  return (
    <button type="button" style={baseStyle} {...props}>
      {children}
    </button>
  );
}
