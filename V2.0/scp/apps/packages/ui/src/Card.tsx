import type { CSSProperties, ReactNode } from 'react';
import { tokens } from './tokens';

export interface CardProps {
  children: ReactNode;
  title?: string;
  style?: CSSProperties;
}

export function Card({ children, title, style }: CardProps) {
  const cardStyle: CSSProperties = {
    border: `1px solid ${tokens.color.border}`,
    borderRadius: tokens.radius.md,
    padding: tokens.space[3],
    backgroundColor: tokens.color.surface,
    boxShadow: tokens.shadow.sm,
    ...style,
  };

  return (
    <div style={cardStyle}>
      {title && (
        <h2
          style={{
            margin: `0 0 ${tokens.space[2]}px`,
            fontSize: tokens.font.sizeLg,
            fontWeight: tokens.font.weightSemibold,
          }}
        >
          {title}
        </h2>
      )}
      {children}
    </div>
  );
}
