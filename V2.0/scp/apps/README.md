# Experience Layer — Client Applications

**Document reference:** [ADR-023](../../docs/00-meta/adr/023-sapphital-platform-os.md) · [Platform OS Ch. 13](../../docs/03-architecture/13-platform-os-architecture.md) · [Experience Layer Roadmap](../docs/EXPERIENCE-LAYER-ROADMAP.md)

> **Terminology:** ADR-023 calls this **Layer 0**. Product docs prefer **Experience Layer** (same thing — all user-facing products under `apps/`).

This directory holds **experience products** — independently versioned Next.js apps that consume the SAPPHITAL SCP API. They are **not** part of the Laravel shell; backend packages expose JSON only.

## Request path

```text
Experience Layer (apps/*)
        ↓
Edge (Cloudflare — tenant, cache, WAF)          ← Phase 1 partial
        ↓
API Gateway / Laravel shell (V2.0/scp)
        ↓
Platform Services → Modules → Connectors
```

## Phase 1 — Nigeria GA (build now)

| App | Product name | Port | Status |
|-----|--------------|------|--------|
| `storefront/` | Shopper experience | 3000 | **Done** (functional) |
| `admin/` | Merchant Operating System | 3001 | **Done** (products, orders, shipments) |
| `platform-admin/` | Platform Operations Center | 3002 | **Done** (functional) |
| `marketing/` | Public website + signup | 3003 | **Done** (functional) |

## Phase 2+ — Scaffolded (README only)

| App | Product name | Phase |
|-----|--------------|-------|
| `customer/` | Customer Experience Portal | 2 |
| `support/` | Internal support & diagnostics | 2 |
| `analytics/` | Merchant & platform analytics | 2 |
| `studio/` | Business Builder (theme/CMS) | 2–3 |
| `developers/` | Developer portal | 3 |
| `vendor/` | Vendor Commerce Center | 3 |
| `marketplace/` | Extension discovery | 3 |
| `partners/` | Agency & reseller portal | 3–5 |
| `ai-studio/` | AI agents, workflows, KB | 2+ |
| `mobile/`, `pos/` | Omnichannel | 4 |

Full map and research alignment: [`docs/EXPERIENCE-LAYER-ROADMAP.md`](../docs/EXPERIENCE-LAYER-ROADMAP.md).

## Shared packages (`apps/packages/`)

| Package | Status |
|---------|--------|
| `ui/` (`@sapphital/scp-ui`) | Stub — evolves to `design-system/` |
| `icons/`, `hooks/`, `types/`, `utils/`, … | Phase 2 |

Backend shared code: `Packages/` (PHP), `sdk/` (public SDKs, Phase 3).

## Where UI lives

| Layer | Path | Has UI? |
|-------|------|---------|
| Experience Layer | `apps/*` | **Yes** — Next.js |
| Shared components | `apps/packages/ui` | **Yes** — React library |
| Storefront themes | `Themes/scp-*` | **Config only** — JSON |
| Platform / Modules / Connectors | `Platform/*`, `Modules/*`, `Connectors/*` | **No** — HTTP APIs only |

Full explanation: [`docs/ARCHITECTURE-UI.md`](../docs/ARCHITECTURE-UI.md)

## Conventions

- Each app ships its own `README.md`, CI workflow, and environment config.
- Apps communicate via documented HTTP APIs only — no Eloquent or cross-package imports.
- Tenant context: edge resolution (storefront) or authenticated session + `X-Tenant-ID` (admin).
- Nigeria-first defaults (NGN, Paystack, NDPA) apply to Phase 1 scaffolds.

## Getting started

```bash
cd apps/<app-name>
npm install
npm run dev
```

API shell from repo root (`V2.0/scp`):

```bash
php artisan serve   # http://localhost:8000/api/health
composer test       # 100 tests
```

See each app's `README.md` for port, spec references, and architecture notes.
