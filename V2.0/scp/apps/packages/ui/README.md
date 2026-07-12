# @sapphital/scp-ui

Shared React UI for SAPPHITAL Experience Layer apps. Evolves into full SDS per [Vol 4 Design System](../../../docs/04-design-system/README.md).

**Architecture:** [ARCHITECTURE-UI.md](../../docs/ARCHITECTURE-UI.md)

## Exports

| Export | Purpose |
|--------|---------|
| `tokens` / `tokens.css` | Semantic design tokens (Vol 4 Ch. 02) |
| `Button` | Primary, secondary, danger actions |
| `Card` | Content grouping |
| `Input` | Labelled field with hint/error |
| `Alert` | Error, success, info, warning banners |
| `Table`, `Th`, `Td` | Admin data tables |
| `AdminShell` | Merchant OS layout + nav |

## Wiring an app (no npm publish required)

**`next.config.ts`** — webpack alias:

```typescript
import path from 'path';

webpack: (config) => {
  config.resolve.alias['@sapphital/scp-ui'] = path.join(__dirname, '../packages/ui/src');
  return config;
},
```

**`tsconfig.json`** — paths + **`layout.tsx`** — import tokens:

```tsx
import '@sapphital/scp-ui/tokens.css';
```

**Status:** `admin/` wired (Phase 1). `storefront/`, `marketing/`, `platform-admin/` next.

## Peer dependencies

- `react` ^19
- `react-dom` ^19
