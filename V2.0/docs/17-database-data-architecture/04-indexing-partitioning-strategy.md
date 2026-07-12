# Chapter 04: Indexing & Partitioning Strategy

**Document ID:** SCP-DB-001-04  
**Version:** 1.0.0  
**Status:** ✅ Active  
**Traceability:** NFR-001, NFR-062, NFR-064  

---

## Purpose

Define **indexing standards** and **table partitioning** for high-volume SCP tables (orders, events, audit).

---

## 1. Indexing Principles

| Principle | Rule |
|-----------|------|
| Tenant first | Composite indexes lead with `tenant_id` |
| Query-driven | Index for admin list filters and storefront lookups |
| Write cost | Avoid redundant indexes; review via `pg_stat_user_indexes` |
| Partial indexes | Use for soft-deleted exclusion: `WHERE deleted_at IS NULL` |

---

## 2. Standard Index Patterns

```sql
-- Admin order list: tenant + status + created
CREATE INDEX idx_orders_tenant_status_created
  ON commerce_orders (tenant_id, status, created_at DESC);

-- Storefront product by handle
CREATE UNIQUE INDEX idx_products_tenant_handle
  ON commerce_products (tenant_id, handle)
  WHERE deleted_at IS NULL;

-- JSONB content search
CREATE INDEX idx_entries_fields_gin
  ON content_entries USING GIN (fields jsonb_path_ops);
```

---

## 3. Partitioning Candidates

| Table | Strategy | Key | Phase |
|-------|----------|-----|-------|
| `audit_events` | RANGE monthly | `created_at` | 2 |
| `payments_webhook_events` | RANGE monthly | `received_at` | 2 |
| `commerce_orders` | RANGE quarterly | `created_at` | 3 |
| `analytics_daily_store` | RANGE yearly | `day` | 2 |

---

## 4. Orders Partition Example

```sql
CREATE TABLE commerce_orders (
  id UUID NOT NULL,
  tenant_id UUID NOT NULL,
  created_at TIMESTAMPTZ NOT NULL,
  -- ...
  PRIMARY KEY (id, created_at)
) PARTITION BY RANGE (created_at);

CREATE TABLE commerce_orders_2026_q3
  PARTITION OF commerce_orders
  FOR VALUES FROM ('2026-07-01') TO ('2026-10-01');
```

Queries must include `created_at` range for partition pruning.

---

## 5. Maintenance

| Task | Frequency | Tool |
|------|-----------|------|
| `VACUUM ANALYZE` hot tables | Daily auto | autovacuum tuned |
| Partition creation | Quarterly job | Horizon scheduled |
| Index bloat check | Weekly | pg_repack or reindex online |
| Slow query review | Weekly | pg_stat_statements |

---

## 6. Nigeria Traffic Peaks

Pre-create partitions before November (Black Friday) and December campaigns. Scale read replica before index-heavy reporting jobs.

---

## Cross-References

- [Chapter 05 — Migrations](./05-migrations-zero-downtime.md)
- [Volume 10 — Infrastructure](../10-infrastructure/README.md)
