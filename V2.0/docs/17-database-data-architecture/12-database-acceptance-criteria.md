# Chapter 12: Database Acceptance Criteria

**Document ID:** SCP-DB-001-12  
**Version:** 1.0.0  
**Status:** ✅ Active  
**Traceability:** ADR-002, ADR-005, ADR-009, ADR-011, NFR-005, NFR-007, NFR-040, NFR-062 – NFR-077, NFR-076, NFR-083  

---

## Purpose

Define **launch and release gates** for SCP's database and data architecture layer. Volume 17 is complete for **Phase 1 Nigeria GA** when all criteria below pass in staging and production verification.

## Scope

- Schema and RLS completeness gates
- Migration and zero-downtime gates
- Performance and isolation gates
- Analytics pipeline gates
- Backup and retention gates
- NDPA data governance gates
- Ongoing regression requirements

## Out of Scope

- Full platform launch criteria (Volume 11 Ch. 07, Volume 13 Ch. 10)

---

## 1. Schema & RLS (Launch Blockers)

- [ ] PostgreSQL 16 deployed in Nigeria/West Africa region (ADR-011)
- [ ] PgBouncer transaction pooling configured with `DISCARD ALL` reset (ADR-005)
- [ ] **100%** of tenant-scoped tables have `tenant_id NOT NULL`
- [ ] **100%** of tenant-scoped tables have `ENABLE ROW LEVEL SECURITY`
- [ ] **100%** of tenant-scoped tables have `FORCE ROW LEVEL SECURITY`
- [ ] **100%** of tenant-scoped tables have `tenant_isolation` policy with `USING` and `WITH CHECK`
- [ ] Platform-global tables documented and exempt list approved
- [ ] UUID v7 primary keys on all tenant business tables
- [ ] Money columns use `BIGINT` minor units — zero floating-point currency columns
- [ ] Cross-module references use UUID without FK — no cross-module JOIN in application repos
- [ ] Database roles defined: `scp_app`, `scp_migrate`, `scp_bi_read`, `scp_admin`, `scp_backup`
- [ ] `scp_app` has no UPDATE/DELETE on `audit_logs`

---

## 2. Tenant Isolation (Launch Blockers)

- [ ] Isolation test suite: **0** cross-tenant reads across API, Eloquent, and direct DB (RLS)
- [ ] Suite covers **100%** of tenant-scoped tables in isolation manifest
- [ ] PgBouncer connection reuse test passes — tenant B cannot see tenant A rows after commit
- [ ] Query without `SET LOCAL app.tenant_id` returns zero tenant rows (fail-closed)
- [ ] Marketplace vendor policy test: vendor staff cannot read other vendor commissions
- [ ] Redis cache isolation test: no cross-tenant cache key collision
- [ ] Suite runs on **every PR** — merge blocked on failure
- [ ] Session-level `SET app.tenant_id` grep scan: **0** occurrences in application code

---

## 3. Indexing & Performance

- [ ] All hot-path queries have composite index with `tenant_id` leading (Chapter 04 catalog)
- [ ] `EXPLAIN ANALYZE` on checkout inventory check: index scan, p95 ≤ 5 ms (staging load)
- [ ] `EXPLAIN ANALYZE` on product by slug: index scan, p95 ≤ 10 ms
- [ ] `EXPLAIN ANALYZE` on order list (50 rows): p95 ≤ 20 ms
- [ ] Primary query p95 ≤ 50 ms under k6 baseline (Volume 13 Ch. 06)
- [ ] No production index created without `CONCURRENTLY`
- [ ] Autovacuum tuning applied to high-churn tables (`orders`, `audit_logs`, `domain_event_outbox`)
- [ ] Query timeout configured: 30 seconds maximum

---

## 4. Migrations & Zero-Downtime

- [ ] Migration CI gate: RLS audit script passes on all new migrations
- [ ] Migration CI gate: `tenant_id` audit passes on all new tenant tables
- [ ] Migration CI gate: new tables registered in isolation manifest
- [ ] Expand-contract pattern demonstrated for at least one column rename in staging
- [ ] Rollback tested: `migrate:rollback --step=1` succeeds on staging
- [ ] Zero-downtime deploy verified: migration + rolling app deploy with no user-visible outage
- [ ] Forbidden operations documented in team runbook (no DISABLE RLS, no session SET)

---

## 5. Event Outbox & Audit

- [ ] `domain_event_outbox` table migrated with RLS and unpublished partial index
- [ ] Outbox relay worker operational — events published within 5 s of commit (p95)
- [ ] Domain events written in same transaction as aggregate save (integration test)
- [ ] `audit_logs` table append-only — UPDATE/DELETE revoked from `scp_app`
- [ ] Mandatory audit events fire for: login, order.paid, refund.issued, tenant.export, admin.impersonation
- [ ] PII in audit `changes` column encrypted (spot-check test)
- [ ] `idempotency_keys` table operational for Paystack webhook deduplication
- [ ] `processed_events` deduplication verified for analytics consumer

---

## 6. Read Replicas & Caching (Phase 2 Gate)

Phase 1: all reads on primary — criteria marked N/A except caching.

- [ ] Redis cache keys use `tenant:{id}:` prefix — CI lint passes
- [ ] Cache invalidation on `ProductUpdated` and `OrderPaid` verified
- [ ] Checkout and inventory queries confirmed on primary only (code audit)
- [ ] (Phase 2) Read replica streaming replication lag p99 ≤ 1 s under normal load
- [ ] (Phase 2) Replica lag alert at 30 s warning, 5 min critical
- [ ] (Phase 2) Dashboard 7d+ history routed to replica
- [ ] (Phase 2) `scp_bi_read` role limited to analytics tables — no PII table access

---

## 7. Analytics Pipeline

- [ ] All five analytics tables migrated: `analytics_daily_store`, `analytics_product_sales`, `analytics_funnel`, `analytics_traffic`, `analytics_vendor`
- [ ] Analytics tables have RLS `tenant_isolation` policy
- [ ] Hourly aggregation jobs operational — lag ≤ 1 h during WAT peak
- [ ] `OrderPaid` event updates daily store and product sales rollups (integration test)
- [ ] Nightly reconciliation job: OLTP vs rollup mismatch ≤ 0.1%
- [ ] Merchant dashboard metrics verified: gross sales, orders, AOV, conversion rate
- [ ] Platform BI aggregates contain no shopper PII (manual review)
- [ ] Real-time order feed uses primary + 30 s cache — not analytics tables

---

## 8. Materialized Views (Phase 2 Gate)

- [ ] (Phase 2) `mv_merchant_weekly_sales`, `mv_merchant_top_products_7d`, `mv_merchant_funnel_30d` created
- [ ] (Phase 2) Unique indexes on all MVs for concurrent refresh
- [ ] (Phase 2) Hourly `REFRESH CONCURRENTLY` job operational
- [ ] (Phase 2) Dashboard queries include mandatory `tenant_id` filter
- [ ] (Phase 2) Platform MVs restricted to admin/BI roles

Phase 1 GA: analytics tables sufficient; MVs optional enhancement.

---

## 9. Backup, Retention & Archival

- [ ] Automated full backup every 6 hours with failure paging
- [ ] Backups encrypted AES-256 at rest in Lagos region (ADR-011)
- [ ] Hot backup retention 30 days verified
- [ ] Quarterly restore drill: RTO ≤ 4 hours (NFR-026)
- [ ] Quarterly RPO validation: data loss within NFR targets
- [ ] Post-restore RLS validation: isolation suite 0 failures
- [ ] Retention jobs operational: cart purge (30d), outbox cleanup (7d), idempotency TTL
- [ ] (Phase 2) WAL archiving and PITR enabled
- [ ] (Phase 2) Audit log monthly partition creation automated

---

## 10. Data Governance & NDPA (Launch Blockers)

- [ ] PII column inventory (Chapter 11) matches production schema
- [ ] Tenant data export end-to-end demonstrated: request → MFA → download → audit
- [ ] Shopper erasure job demonstrated with financial record retention exception
- [ ] Tenant hard delete job tested on staging tenant — full purge with audit trail
- [ ] No PAN/CVV columns in any table (PCI SAQ A — ADR-004)
- [ ] RoPA database section current and includes all Phase 1 PII tables
- [ ] Primary production database in Nigeria/West Africa confirmed (ADR-011)
- [ ] Cross-border subprocessor register includes database-related services

---

## 11. Ongoing Regression (Post-GA)

| Gate | Frequency | Owner |
|------|-----------|-------|
| Isolation test suite | Every PR | Engineering |
| RLS audit on new migrations | Every PR | CI |
| Slow query review (top 20) | Weekly | Platform |
| Index bloat review | Monthly | DBA |
| Backup restore drill | Quarterly | DevOps |
| Reconciliation job alert | Daily | Analytics |
| RoPA schema review | On PII schema change | DPO + Engineering |
| Retention purge job health | Daily | Platform |

---

## 12. Sign-Off

| Role | Sign-Off | Date |
|------|----------|------|
| Lead Architect | Schema, RLS, migrations | |
| Platform Engineering | Performance, backups, replicas | |
| Security Lead | Isolation suite, audit, encryption | |
| DPO | NDPA, RoPA, erasure/export | |
| DevOps | Backup drills, monitoring alerts | |

Volume 17 Phase 1 Nigeria GA is **approved** when Sections 1, 2, 4, 5, 7, 9, and 10 pass with sign-off from all roles above.

---

## References

- [Volume 11 Ch. 07 — Security Acceptance Criteria](../11-security/07-acceptance-criteria.md)
- [Volume 13 Ch. 04 — Tenant Isolation Test Suite](../13-testing/04-tenant-isolation-test-suite.md)
- [Volume 14 Ch. 10 — Operations Acceptance Criteria](../14-operations/10-operations-acceptance-criteria.md)
- [Volume 16 Ch. 08 — SaaS Acceptance Criteria](../16-saas-multi-tenancy/08-saas-acceptance-criteria.md)
- [ADR-002](../00-meta/adr/002-multi-tenancy-shared-db-rls.md)
- [ADR-005](../00-meta/adr/005-rls-pgbouncer-set-local.md)
