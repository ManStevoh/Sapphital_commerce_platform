# ADR-023: SAPPHITAL Platform OS (Products, Services, Connectors)

**Date:** 2026-07-12  
**Status:** Accepted  
**Deciders:** Stephen Musyoka Makola  
**Volume Reference:** Volume 3 Ch. 13

## Context

SCP documentation evolved from "Laravel modules in `app/`" toward bounded contexts. Stephen's architectural decision goes further: **do not build "modules" as a flat list** — build a **modular business operating system** analogous to Windows + installed applications.

Commerce is **Office on Windows**. Identity, Billing, and AI are **platform infrastructure**, not product features buried inside Commerce.

This separates a medium-sized Laravel app from a platform that scales 10+ years.

## Decision

Adopt the **SAPPHITAL Platform OS** layering model and **physical repository layout** outside `app/`:

```text
SAPPHITAL Platform
├── Client Applications    (apps/ — admin, storefront, mobile, POS)
├── Platform Kernel          (mandatory — knows no Commerce/ERP/CRM)
├── Platform Services        (reusable engines)
├── Business Products        (installable applications)
├── Extensions               (optional features)
├── Connectors               (external systems)
├── AI Skills                (optional agents)
├── Themes                   (packages)
├── Packages                 (shared libs — Money, theme-sdk, contracts)
└── Developer Marketplace    (Phase 3+)
```

### Physical Layout (Normative)

```text
/
├── apps/              # Client runtimes (admin, storefront, visual-builder, mobile, POS)
├── Platform/          # Kernel + platform services (NOT inside app/)
├── Modules/           # Business products + extensions
├── Connectors/        # Paystack, M-Pesa, Stripe, QuickBooks, Cloudflare, …
├── Themes/            # Theme packages
├── AI/                # Agent/skill packages (consumes Platform Intelligence)
├── Packages/          # Shared libraries (Money, theme-sdk, contracts, testing)
├── Bootstrap/
├── Config/
├── Database/          # Shared migrations only
├── app/               # Laravel application shell (thin)
└── …
```

**Rule:** Business product code lives in `Modules/`, **never** scattered in `app/Models`.

### Layer Mapping (Existing ADRs)

| Layer | Examples | Prior ADR |
|-------|----------|-----------|
| Kernel | Tenancy, Identity, Billing, Provisioning, Module Manager | ADR-002, ADR-022 |
| Platform Services | Payment Engine (FSL), Tax, Currency, Workflow, Notifications | ADR-019, ADR-020 |
| Business Products | Commerce, Marketplace, ERP, CRM, POS, Learning | ADR-001 |
| Connectors | Paystack, Flutterwave, M-Pesa adapters | ADR-019 |
| AI Skills | CatalogAgent, MarketingAgent, SupportAgent | ADR-020 |
| Themes | Lagos Atelier, Terminal Tech | ADR-003 |

### Module Manifest (`module.json`)

Every installable unit declares: name, semver, requires[], permissions[], routes, migrations, events, menus. **Module Manager** verifies dependency graph before install/upgrade.

### Module Contracts (Mandatory)

Each package ships: `README.md`, `CHANGELOG.md`, `ARCHITECTURE.md`, `API.md`, `PERMISSIONS.md`, `EVENTS.md`, `CONFIG.md`, `TESTING.md`, `UPGRADE.md`, `module.json`.

### Deployment Model

Remains **modular monolith** (ADR-001) — one deployable artifact; packages are logical and physical boundaries with independent semver and CI pipelines.

## Alternatives Considered

| Alternative | Why Rejected |
|-------------|--------------|
| Everything in `app/Modules` | No OS separation; platform vs product conflated |
| Microservices per product Day 1 | Team size; ADR-001 |
| Flat `/Modules` only (no Platform/) | Identity/Billing would look like Commerce plugins |
| WordPress-style plugins only | No enterprise dependency graph or licensing |

## Consequences

### Positive

- 20-dev team can own packages independently; minimal merge conflicts
- Per-module CI/CD and semver
- Commerce uninstallable without forking kernel
- Connectors swappable without touching Commerce

### Negative

- Module Manager complexity
- Dependency resolution and upgrade testing overhead
- Migration path from current `App\Domains\*` namespace

### Neutral

- Laravel still boots via thin `app/` shell registering Platform + Modules
- SCP Phase 1 ships Kernel + Commerce + core Connectors; ERP/Learning later

## References

- [Volume 3 Ch. 13](../../03-architecture/13-platform-os-architecture.md)
- ADR-001, ADR-019, ADR-020, ADR-022
