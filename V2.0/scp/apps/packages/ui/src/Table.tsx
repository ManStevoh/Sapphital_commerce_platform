import type { ReactNode } from 'react';
import { tokens } from './tokens';

export interface TableProps {
  'aria-label': string;
  children: ReactNode;
}

export function Table({ 'aria-label': ariaLabel, children }: TableProps) {
  return (
    <div
      style={{
        overflowX: 'auto',
        border: `1px solid ${tokens.color.border}`,
        borderRadius: tokens.radius.md,
        backgroundColor: tokens.color.surface,
      }}
    >
      <table
        aria-label={ariaLabel}
        style={{
          width: '100%',
          borderCollapse: 'collapse',
        }}
      >
        {children}
      </table>
    </div>
  );
}

export function Th({ children }: { children: ReactNode }) {
  return (
    <th
      style={{
        textAlign: 'left',
        padding: `${tokens.space[1]}px ${tokens.space[2]}px`,
        borderBottom: `1px solid ${tokens.color.border}`,
        fontSize: tokens.font.sizeSm,
        color: tokens.color.textSecondary,
        fontWeight: tokens.font.weightSemibold,
      }}
    >
      {children}
    </th>
  );
}

export function Td({ children }: { children: ReactNode }) {
  return (
    <td
      style={{
        padding: `${tokens.space[1]}px ${tokens.space[2]}px`,
        borderBottom: `1px solid ${tokens.color.divider}`,
        verticalAlign: 'top',
      }}
    >
      {children}
    </td>
  );
}
