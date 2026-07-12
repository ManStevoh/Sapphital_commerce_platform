# Shared Frontend Packages

Cross-app React/TypeScript libraries for the **Experience Layer** only.

Backend shared code lives in `Packages/` (PHP) at repo root — not here.

## Phase 1

| Package | NPM name | Status |
|---------|----------|--------|
| `ui/` | `@sapphital/scp-ui` | Stub — Button, Card, Input |

## Phase 2 (planned)

| Package | Purpose |
|---------|---------|
| `design-system/` | Vol 4 SDS — tokens, typography, a11y (evolves from `ui/`) |
| `icons/` | `@sapphital/scp-icons` |
| `hooks/` | Shared React hooks (tenant, auth, cart) |
| `types/` | OpenAPI-generated API types |
| `utils/` | NGN formatting, dates (Africa/Lagos) |
| `config/` | Shared ESLint/TS/tailwind presets |
| `auth/` | Bearer + tenant session helpers |
| `forms/` | Validated form primitives |
| `tables/` | Data tables for admin surfaces |
| `charts/` | Analytics charts |

## Phase 3+

`editor/`, `theme/`, `analytics/`, `ai/` — see [EXPERIENCE-LAYER-ROADMAP.md](../../docs/EXPERIENCE-LAYER-ROADMAP.md).

## Import pattern

```json
{
  "dependencies": {
    "@sapphital/scp-ui": "file:../packages/ui"
  }
}
```
