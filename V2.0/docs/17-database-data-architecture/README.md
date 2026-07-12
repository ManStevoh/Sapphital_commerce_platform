# Volume 17: Database & Data Architecture

**Document ID:** SCP-DB-001  
**Version:** 1.0.0  
**Status:** ✅ Active  
**Depends On:** Volume 3 (Architecture), Volume 10 (Infrastructure), Volume 11 (Security)  
**Owner:** Sapphital Learning Company  
**Lead Architect:** Stephen Musyoka Makola  

---

## Purpose

Volume 17 is the **authoritative database and data-layer specification** for SCP — PostgreSQL schema design, RLS tenant isolation, indexing, migrations, event/outbox patterns, analytics OLAP paths, backups, and Nigeria NDPA data governance.

## Scope

- OLTP PostgreSQL architecture (primary market: Nigeria)
- Row-level security and connection context (ADR-002, ADR-005)
- Schema conventions, partitioning, and zero-downtime migrations
- Event outbox, audit immutability (ADR-009)
- Read replicas, caching, materialized views
- Analytics pipeline and merchant reporting tables
- Backup, retention, archival, NDPA compliance

## Out of Scope

- Application ORM implementation details per module (Volumes 5–9)
- Vendor-specific BI dashboards (Volume 8)
- ClickHouse cluster ops until Phase 4 (referenced as export target)

## Chapters

| # | Chapter | Status |
|---|---------|--------|
| 01 | [Database Architecture Overview](./01-database-architecture-overview.md) | ✅ Active |
| 02 | [PostgreSQL Schema Design](./02-postgresql-schema-design.md) | ✅ Active |
| 03 | [Row-Level Security Policies](./03-row-level-security-policies.md) | ✅ Active |
| 04 | [Indexing & Partitioning Strategy](./04-indexing-partitioning-strategy.md) | ✅ Active |
| 05 | [Migrations & Zero-Downtime](./05-migrations-zero-downtime.md) | ✅ Active |
| 06 | [Event Outbox & Audit Tables](./06-event-outbox-audit-tables.md) | ✅ Active |
| 07 | [Read Replicas & Caching](./07-read-replicas-caching.md) | ✅ Active |
| 08 | [Analytics Pipeline & OLAP](./08-analytics-pipeline-olap.md) | ✅ Active |
| 09 | [Materialized Views & Reporting](./09-materialized-views-reporting.md) | ✅ Active |
| 10 | [Backup, Retention & Archival](./10-backup-retention-archival.md) | ✅ Active |
| 11 | [Data Governance & NDPA](./11-data-governance-ndpa.md) | ✅ Active |
| 12 | [Database Acceptance Criteria](./12-database-acceptance-criteria.md) | ✅ Active |

## Related Volumes

- [Volume 14 Ch. 11 — Database & Analytics (Operations view)](../14-operations/11-database-analytics-architecture.md)
- [Volume 3 Ch. 05 — Multi-Tenancy](../03-architecture/05-multi-tenancy-and-isolation.md)
- ADR-002, ADR-005, ADR-009, ADR-011

## Acceptance Criteria (Volume Complete)

- [x] All 12 chapters published with traceability to ADRs and NFRs
- [x] RLS policy patterns documented with SQL examples
- [x] Nigeria data residency and NDPA retention rules explicit
- [x] Zero-downtime migration playbook defined

---

**Review cycle:** Quarterly with platform engineering and DPO.
