# Platform Billing

**Package:** `platform/billing`  
**Version:** 0.2.0  
**Layer:** Platform Kernel (Layer 1)  
**Traceability:** ADR-023, SCP-TASK-0004, Vol 16 Ch. 03–04

## Purpose

SaaS billing, subscriptions, invoices, and plan entitlements for the SAPPHITAL Platform OS. Nigeria-first: all plan prices stored as integer kobo (`NGN`).

## Phase 1.3 Scope (SCP-TASK-0004)

- **Plans** — Starter, Growth, Pro catalog with quota limits
- **Subscriptions** — per-tenant plan assignment and lifecycle status
- **Invoices** — draft/open/paid/void billing records (schema only)
- **EntitlementChecker** — `canAddProduct(tenantId)` enforces `products.max` from active plan
- **Public API** — plan listing; subscription lookup stub (no auth yet)

## Database

| Table | Purpose |
|-------|---------|
| `plans` | Catalog: slug, `price_ngn` (kobo), `product_limit`, `staff_limit`, `custom_domain` |
| `subscriptions` | Tenant plan: status (`provisioning` … `deleted`), trial/period dates |
| `invoices` | Billing documents: amounts in kobo, `lines` JSON, optional `paystack_reference` |

Default plans (seeded in migration):

| Slug | Monthly (NGN) | `price_ngn` (kobo) | Products | Staff |
|------|---------------|--------------------|----------|-------|
| starter | ₦15,000 | 1,500,000 | 100 | 2 |
| growth | ₦45,000 | 4,500,000 | 1,000 | 10 |
| pro | ₦120,000 | 12,000,000 | 10,000 | 50 |

## API

| Method | Path | Auth | Description |
|--------|------|------|-------------|
| GET | `/api/v1/platform/billing/health` | — | Package health |
| GET | `/api/v1/platform/billing/plans` | Public | List all plans |
| GET | `/api/v1/platform/billing/subscriptions/{tenantId}` | Stub | Tenant subscription or 404 |

## Services

- **EntitlementChecker** — resolves active/trial subscription, compares `TenantProductCounter` against plan `product_limit` (fail-closed if no subscription)
- **TenantProductCounter** — contract; default `NullTenantProductCounter` returns 0 until Catalog integration

## References

- [Vol 16 Ch. 03 — Plans & Entitlements](../../../docs/16-saas-multi-tenancy/03-plans-and-entitlements.md)
- [Vol 16 Ch. 04 — Billing & Invoicing](../../../docs/16-saas-multi-tenancy/04-billing-and-invoicing.md)
- [Platform OS Ch. 13 §3](../../../docs/03-architecture/13-platform-os-architecture.md)
