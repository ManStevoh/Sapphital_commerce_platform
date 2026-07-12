# Chapter 02: PostgreSQL Schema Design

**Document ID:** SCP-DB-001-02  
**Version:** 1.0.0  
**Status:** ✅ Active  
**Traceability:** ADR-002, FR-025, NFR-062  

---

## Purpose

Define **schema conventions**, core entity patterns, and naming standards for all SCP modules on PostgreSQL.

## Scope

- Naming and types
- Standard columns
- FK and soft-delete patterns
- Money and locale types
- Representative table catalog

## Out of Scope

- Full DDL export (generated at implementation)
- Module-specific business rules (Volumes 5–9)

---

## 1. Conventions

| Rule | Standard |
|------|----------|
| Table names | `snake_case`, plural (`commerce_orders`) |
| Primary keys | `id UUID DEFAULT gen_random_uuid()` |
| Tenant scope | `tenant_id UUID NOT NULL REFERENCES platform_tenants(id)` |
| Timestamps | `created_at`, `updated_at` TIMESTAMPTZ UTC |
| Soft delete | `deleted_at TIMESTAMPTZ NULL` where applicable |
| Money | `amount_minor BIGINT NOT NULL`, `currency CHAR(3) NOT NULL` |
| Enums | PostgreSQL ENUM or `CHECK` on text; prefer lookup tables for extensibility |
| JSON | `JSONB` with GIN index when queried |

---

## 2. Standard Audit Columns

Tenant-scoped mutable tables include:

```sql
created_at TIMESTAMPTZ NOT NULL DEFAULT now(),
updated_at TIMESTAMPTZ NOT NULL DEFAULT now(),
created_by UUID NULL REFERENCES platform_users(id),
updated_by UUID NULL REFERENCES platform_users(id)
```

Immutable facts (payments, audit) omit `updated_at` or block updates via trigger.

---

## 3. Core Platform Tables

| Table | Purpose |
|-------|---------|
| `platform_tenants` | Merchant store registry |
| `platform_users` | Staff and platform admins |
| `platform_tenant_users` | Membership, roles |
| `platform_plans` | SaaS plan definitions |
| `platform_subscriptions` | Tenant billing state |
| `platform_domains` | Custom domain mapping |

---

## 4. Commerce Core Tables

| Table | Purpose |
|-------|---------|
| `commerce_products` | Catalog products |
| `commerce_variants` | SKUs, price linkage |
| `commerce_collections` | Merchandising groups |
| `commerce_carts` | Active carts |
| `commerce_orders` | Order header |
| `commerce_order_lines` | Line items |
| `commerce_inventory_levels` | Stock by location |
| `commerce_price_lists` | Scheduled pricing |

**Order number:** human-readable `order_number` unique per tenant (`UNIQUE (tenant_id, order_number)`).

---

## 5. Payments Tables

| Table | Purpose |
|-------|---------|
| `payments_intents` | Checkout payment attempt |
| `payments_transactions` | PSP-confirmed transactions |
| `payments_refunds` | Refund records |
| `payments_webhook_events` | Idempotent webhook log |

No PAN or CVV columns — SAQ A (ADR-004).

---

## 6. CMS & Learning Tables

| Table | Purpose |
|-------|---------|
| `content_types` | Tenant content type schemas |
| `content_entries` | Structured entries (JSONB fields) |
| `content_pages` | Theme-bound pages |
| `content_releases` | Scheduled publish containers (ADR-014) |
| `content_media` | Media metadata; binary in R2 |
| `learning_courses` | Course hierarchy root |
| `learning_lessons` | Lesson content (BlockNote JSONB) |
| `learning_enrollments` | Post-purchase access (ADR-016) |

---

## 7. Marketplace Tables

| Table | Purpose |
|-------|---------|
| `marketplace_vendors` | Seller profiles |
| `marketplace_commissions` | Fee rules |
| `marketplace_payouts` | Vendor settlement batches |

---

## 8. Indexing Defaults

Every tenant-scoped table:

```sql
CREATE INDEX idx_{table}_tenant_id ON {table} (tenant_id);
```

Foreign keys from child → parent always indexed on the FK column.

---

## 9. Migration Ownership

| Change type | Owner | Review |
|-------------|-------|--------|
| New module tables | Module lead | Architect |
| RLS policy change | Security + module | Mandatory security review |
| Partition strategy | Platform | DBA sign-off |
| Breaking column rename | Multi-module | Release playbook (Ch. 05) |

---

## Cross-References

- [Chapter 03 — RLS](./03-row-level-security-policies.md)
- [Chapter 04 — Indexing](./04-indexing-partitioning-strategy.md)
- [Volume 5 — Commerce Engine](../05-commerce-engine/README.md)
