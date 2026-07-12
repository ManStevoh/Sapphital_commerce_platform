# ADR-022: Tenant Provisioning Engine (TPE)

**Date:** 2026-07-12  
**Status:** Accepted  
**Deciders:** Stephen Musyoka Makola  
**Volume Reference:** Volume 16 Ch. 10; Volume 3 Ch. 05

## Context

Onboarding, domains, subscriptions, multi-store, AI workspace, and theme installation were documented separately. At Shopify scale, these must be **one intelligent system** — not scattered jobs and manual steps.

Merchants click **Create Store** and expect a **fully initialized business environment** within minutes, not an empty dashboard.

## Decision

Create the **Tenant Provisioning Engine (TPE)** — a core platform service orchestrating everything after registration.

### TPE Owns

- Post-registration pipeline (subscription → tenant → workspace → store)
- Multi-store creation within plan entitlements
- Default subdomain (`{slug}.shops.sapphital.africa`) via wildcard DNS
- Custom domain linking, DNS polling, SSL (Cloudflare for SaaS)
- Queue-based async provisioning (theme, AI, storage, analytics, queues)
- Store templates, duplication, enterprise cloning
- Entitlement-driven limits (no hardcoded plan checks in provisioners)

### TPE Does Not Own

- Subscription billing logic (Volume 16 Billing — TPE reads entitlements)
- Storefront rendering (Theme Engine)
- Payment gateway credentials (FSL — TPE seeds recommendations only)
- AI agent execution (Intelligence Platform — TPE creates workspace shell)

### Architecture

```text
Register / Create Store
        ↓
Tenant Provisioning Engine (orchestrator)
        ↓
┌───────┴───────┬───────────┬───────────┬───────────┐
│ Tenant DB     │ Subdomain │ AI Workspace │ Theme     │
│ Roles/Storage │ SSL/DNS   │ Analytics   │ Templates │
└───────────────┴───────────┴───────────┴───────────┘
        ↓
Store Live (draft or published)
```

### DNS Strategy (Normative)

**Wildcard subdomain** at edge — no per-store Nginx/cPanel vhost:

```text
*.shops.sapphital.africa → Cloudflare → Load Balancer → Laravel → TenantResolver → Store
```

Custom domains: DNS verify → Cloudflare custom hostname → auto SSL → link to store.

Rejected for scale: cPanel/SPanel per-subdomain API calls at thousands of stores.

### Hierarchy

```text
User (SAPPHITAL Account) → Organization → Tenant → Store(s) → Domain(s)
```

One login, multiple stores per tenant (plan-limited).

## Consequences

### Positive

- Single orchestration point; observable provisioning progress
- Multi-store (Samsung-style) without duplicate accounts
- Subscription changes propagate through entitlements API
- Franchise clone and enterprise regional expansion supported

### Negative

- Orchestrator complexity; saga/compensation on partial failure
- Wildcard DNS + resolver must be bulletproof (security critical)

## References

- [Volume 16 Ch. 10](../../16-saas-multi-tenancy/10-tenant-provisioning-engine.md)
- ADR-002, ADR-008, ADR-021, ADR-023
