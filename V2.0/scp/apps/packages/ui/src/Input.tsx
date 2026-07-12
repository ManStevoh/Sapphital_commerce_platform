import type { InputHTMLAttributes } from 'react';
import { tokens } from './tokens';

export interface InputProps extends InputHTMLAttributes<HTMLInputElement> {
  label?: string;
  hint?: string;
  error?: string;
}

export function Input({ label, hint, error, id, style, ...props }: InputProps) {
  const inputId = id ?? (label ? label.toLowerCase().replace(/\s+/g, '-') : undefined);
  const hasError = Boolean(error);

  const inputStyle: React.CSSProperties = {
    display: 'block',
    width: '100%',
    padding: `${tokens.space[1]}px ${tokens.space[2]}px`,
    borderRadius: tokens.radius.sm,
    border: `1px solid ${hasError ? tokens.color.error : tokens.color.border}`,
    fontSize: tokens.font.sizeBase,
    color: tokens.color.text,
    backgroundColor: tokens.color.surface,
    boxSizing: 'border-box',
    ...style,
  };

  return (
    <label style={{ display: 'block', marginBottom: tokens.space[2] }}>
      {label && (
        <span
          style={{
            display: 'block',
            marginBottom: tokens.space[0.5],
            fontWeight: tokens.font.weightMedium,
            fontSize: tokens.font.sizeSm,
            color: tokens.color.text,
          }}
        >
          {label}
        </span>
      )}
      <input id={inputId} style={inputStyle} aria-invalid={hasError} {...props} />
      {hint && !error && (
        <span
          style={{
            display: 'block',
            marginTop: tokens.space[0.5],
            fontSize: tokens.font.sizeSm,
            color: tokens.color.textMuted,
          }}
        >
          {hint}
        </span>
      )}
      {error && (
        <span
          style={{
            display: 'block',
            marginTop: tokens.space[0.5],
            fontSize: tokens.font.sizeSm,
            color: tokens.color.error,
          }}
        >
          {error}
        </span>
      )}
    </label>
  );
}
