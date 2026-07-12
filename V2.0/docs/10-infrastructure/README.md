# Volume 10: Infrastructure & DevOps

**Document ID:** SCP-INF-001  
**Version:** 1.0.0  
**Status:** ✅ Active  
**Depends On:** Volume 3 (Architecture), ADR-001, ADR-005, ADR-007, ADR-008, ADR-011  
**Owner:** Sapphital Learning Company  

---

## Purpose

This volume defines how SCP is **built, deployed, operated, and scaled** from a one-engineer MVP through enterprise multi-region commerce. Infrastructure choices prioritize **Nigeria (Lagos) as the primary production region** (ADR-011), **Cloudflare at the edge** (ADR-008), and a **phased Docker → Kubernetes** path that avoids premature operational complexity.

## Scope

- Cloud architecture and Africa region strategy
- Compute (FrankenPHP + Laravel Octane)
- Data layer (PostgreSQL, Redis, Meilisearch)
- Object storage and CDN (Cloudflare R2)
- CI/CD, environments, secrets, and config
- Monitoring, SLOs, alerting, and observability
- Backup, disaster recovery, and business continuity
- Scaling phases and Kubernetes migration criteria
- Cost models by growth phase
- Operational runbooks

## Out of Scope

- Application security controls (Volume 11)
- Detailed module deployment manifests per domain (Volume 3, implementation repos)
- Legal SLA contract text (Volume 19)
- Payment provider integration (Volume 5)

## Chapters

| # | Chapter | Status |
|---|---------|--------|
| 01 | [Infrastructure Overview](./01-infrastructure-overview.md) | ✅ Active |
| 02 | [Cloud Architecture — Nigeria & Africa](./02-cloud-architecture-nigeria-africa.md) | ✅ Active |
| 03 | [Compute — FrankenPHP & Octane](./03-compute-frankenphp-octane.md) | ✅ Active |
| 04 | [PostgreSQL, Redis & Meilisearch](./04-postgresql-redis-meilisearch.md) | ✅ Active |
| 05 | [Storage, CDN & Cloudflare](./05-storage-cdn-cloudflare.md) | ✅ Active |
| 06 | [CI/CD Pipeline](./06-ci-cd-pipeline.md) | ✅ Active |
| 07 | [Environments & Configuration](./07-environments-and-config.md) | ✅ Active |
| 08 | [Monitoring & Observability](./08-monitoring-observability.md) | ✅ Active |
| 09 | [Backup & Disaster Recovery](./09-backup-disaster-recovery.md) | ✅ Active |
| 10 | [Scaling Path & Kubernetes](./10-scaling-path-kubernetes.md) | ✅ Active |
| 11 | [Cost Models](./11-cost-models.md) | ✅ Active |
| 12 | [Runbooks](./12-runbooks.md) | ✅ Active |

## Platform SLO Summary

| SLO | Target | Measurement Window | Error Budget |
|-----|--------|-------------------|--------------|
| **Availability** | 99.9% | 30-day rolling | 43.2 min/month |
| **Storefront LCP (mobile p75)** | ≤ 2.0 s | 7-day rolling | 5% sessions may exceed |
| **API read latency (p95)** | ≤ 200 ms | 7-day rolling | 1% requests may exceed |
| **API write latency (p95)** | ≤ 500 ms | 7-day rolling | 1% requests may exceed |
| **Search autocomplete (p95)** | ≤ 100 ms | 7-day rolling | 1% requests may exceed |
| **Background job lag (p95)** | ≤ 5 s | 24-hour rolling | 5% jobs may exceed |
| **RTO (disaster recovery)** | ≤ 4 hours | Per incident | N/A |
| **RPO (data loss)** | ≤ 6 hours | Per incident | N/A |

Full SLO definitions, SLIs, and alerting thresholds are in [Chapter 08](./08-monitoring-observability.md).

## Phased Deployment Model

```text
Phase 1 (MVP, 0–500 merchants)
  Docker Compose on single Lagos-region VM
  FrankenPHP Octane, PostgreSQL, Redis, Meilisearch, R2
  Cloudflare proxy + WAF + CDN

Phase 2 (Growth, 500–5,000 merchants)
  Multi-VM Docker Compose or Swarm
  PgBouncer, PostgreSQL read replica, horizontal Horizon workers
  Zero-downtime deploys, staging parity

Phase 3 (Scale, 5,000–10,000 merchants)
  Load-balanced app tier, extracted search/AI workers
  Managed observability stack, automated DR drills

Phase 4 (Enterprise, 10,000+ merchants)
  Kubernetes (K8s) with HPA, multi-AZ
  Kenya/East Africa region for KE merchants
  Optional EU/US regions (GDPR tier)
```

Detail: [Chapter 10 — Scaling Path & Kubernetes](./10-scaling-path-kubernetes.md).

## Volume Acceptance Criteria

Volume 10 is **complete for Phase 1 Nigeria launch** when all criteria below pass.

### Infrastructure & Residency

- [ ] Production compute, PostgreSQL, Redis, and primary backups in **Nigeria/West Africa** per ADR-011
- [ ] RoPA and subprocessor register list Cloudflare, R2, and hosting provider with transfer mechanism
- [ ] All tenant-scoped media stored in tenant-aligned R2 prefixes; no cross-tenant object ACL leaks verified

### Compute & Runtime

- [ ] FrankenPHP Octane serves production traffic; PHP-FPM not used in production
- [ ] `/health` and `/ready` endpoints return correct status; load balancer/Cloudflare health checks configured
- [ ] Octane worker memory and max-request limits documented and enforced
- [ ] Graceful shutdown completes in-flight requests within 30 seconds

### Data Layer

- [ ] PostgreSQL 16+ with PgBouncer transaction pooling and `SET LOCAL app.tenant_id` (ADR-005)
- [ ] Redis used for cache, sessions, queues (Horizon), and rate limits; persistence policy documented
- [ ] Meilisearch indexes tenant-scoped; reindex runbook tested once
- [ ] Automated backups every 6 hours; restore drill to staging completed within RTO (NFR-025–027)

### Edge & Storage

- [ ] Cloudflare proxy active; TLS 1.3 full-strict to origin (ADR-008)
- [ ] WAF in blocking mode after tuning period; rate limits return 429 with `Retry-After`
- [ ] R2 buckets encrypted at rest; lifecycle rules for orphaned uploads configured

### CI/CD & Environments

- [ ] `main` branch deploys to staging automatically; production requires approval gate
- [ ] CI blocks merge on: unit tests, tenant isolation suite, critical/high CVE scan, lint
- [ ] Staging environment mirrors production topology (same Docker services, scaled down)
- [ ] Zero secrets in repository (gitleaks); secrets injected via encrypted env or vault (ADR-007)

### Observability & SLOs

- [ ] Structured JSON logs shipped to centralized store; 90-day hot retention (NFR-070)
- [ ] Prometheus-compatible metrics for request rate, latency, errors, queue depth
- [ ] OpenTelemetry trace propagation from HTTP → DB → queue verified on sample flows
- [ ] External synthetic uptime checks at 1-minute intervals from Nigeria and Kenya probe locations
- [ ] PagerDuty (or equivalent) on-call rotation live; P1 alert fires within 5 minutes on injected failure test

### Disaster Recovery

- [ ] Backup restore runbook executed; RTO ≤ 4 hours demonstrated in tabletop
- [ ] Cross-region DR copy documented in RoPA (if enabled); encryption verified
- [ ] Database migration rollback procedure documented and tested once

### Operational Readiness

- [ ] Runbooks in Chapter 12 reviewed by on-call engineer
- [ ] Deployment, rollback, and incident runbooks exercised in staging
- [ ] Cost dashboard tracks compute, storage, egress, and Cloudflare usage against Phase 1 budget

**Sign-off roles:** Lead Architect, DevOps/Platform owner (TBD), Security reviewer (Volume 11 cross-check).

## Related ADRs

- [ADR-001](../00-meta/adr/001-modular-monolith-over-microservices.md) — Modular monolith deployment model
- [ADR-005](../00-meta/adr/005-rls-pgbouncer-set-local.md) — PgBouncer + RLS session context
- [ADR-007](../00-meta/adr/007-secrets-management.md) — Secrets management
- [ADR-008](../00-meta/adr/008-edge-security-cloudflare.md) — Cloudflare edge + R2
- [ADR-011](../00-meta/adr/011-data-residency-africa.md) — Nigeria-first data residency

## NFR Traceability

| NFR Range | Topic |
|-----------|-------|
| NFR-001 – NFR-012 | Performance (edge cache, Octane, CDN) |
| NFR-013 – NFR-020 | Scalability phases |
| NFR-021 – NFR-028 | Availability, backup, zero-downtime deploy |
| NFR-062 – NFR-070 | Observability |
| NFR-071 | Data residency (Lagos primary) |
| NFR-076 | Zero-downtime migrations |
