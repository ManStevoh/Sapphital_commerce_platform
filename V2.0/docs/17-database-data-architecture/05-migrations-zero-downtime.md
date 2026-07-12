# Chapter 05: Migrations & Zero-Downtime

**Document ID:** SCP-DB-001-05  
**Version:** 1.0.0  
**Status:** ✅ Active  
**Traceability:** NFR-065, ADR-001  

---

## Purpose

Define **safe schema migration** practices for continuous deployment without checkout downtime.

---

## 1. Migration Tooling

- Laravel migrations as source of truth
- Squashed baseline per major release
- `scp_migration` role with BYPASSRLS for DDL only in CI/CD
- All migrations idempotent where possible (`IF NOT EXISTS`)

---

## 2. Expand–Contract Pattern

| Phase | Action | App compatibility |
|-------|--------|-------------------|
| Expand | Add nullable column / new table | Old + new code |
| Migrate | Backfill via batch job | Both read new |
| Contract | Drop old column / enforce NOT NULL | New code only |

Never drop columns in same release as code removal.

---

## 3. Dangerous Operations

| Operation | Risk | Safe approach |
|-----------|------|---------------|
| `ADD COLUMN NOT NULL` | Table lock | Add nullable → backfill → set NOT NULL |
| `CREATE INDEX` on large table | Write block | `CREATE INDEX CONCURRENTLY` |
| `ALTER TYPE` | Rewrite | New column + copy + swap |
| RLS policy change | Access leak | Deploy policy in transaction; test in staging |

---

## 4. Zero-Downtime Checklist

1. Migration reviewed by module + platform owner
2. Rollback migration prepared
3. Backfill job tested on staging snapshot
4. Feature flag gates code reading new schema
5. Monitor error rate and p95 latency post-deploy
6. Contract phase scheduled ≥ 7 days after expand

---

## 5. Long-Running Backfills

```sql
-- Batch update pattern
UPDATE commerce_orders
SET new_status = mapped_status
WHERE id IN (
  SELECT id FROM commerce_orders
  WHERE new_status IS NULL
  LIMIT 5000
  FOR UPDATE SKIP LOCKED
);
```

Run via Horizon job with sleep between batches.

---

## 6. Rollback

- DDL rollback migration in repo
- Data backfills may be irreversible — require snapshot before contract phase
- PITR restore last resort (Chapter 10)

---

## Cross-References

- [Volume 13 Ch. 09 — CI Quality Gates](../13-testing/09-ci-quality-gates.md)
- [Volume 21 — Implementation Playbooks](../21-implementation-playbooks/README.md)
