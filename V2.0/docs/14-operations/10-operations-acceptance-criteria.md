# Chapter 10: Operations Acceptance Criteria

**Document ID:** SCP-OPS-001-10  
**Version:** 1.0.0  
**Status:** 📝 Draft  
**Traceability:** NFR-021 – NFR-028, NFR-062 – NFR-070, Volume 11 Ch 07  

---

Volume 14 is **complete for Phase 1 Nigeria launch** when all criteria below pass.

---

## 1. Observability

- [ ] Prometheus metrics exported for API, workers, PostgreSQL, Redis, queue (NFR-063)
- [ ] OpenTelemetry traces on checkout and payment paths (NFR-064)
- [ ] Structured JSON logs with `tenant_id`, `trace_id` (NFR-062)
- [ ] Sentry error tracking with PII scrubbing verified (NFR-066)
- [ ] External synthetics from Lagos probe at 1-min intervals (NFR-067)
- [ ] Grafana golden-signal dashboard operational (Chapter 01)

## 2. SLOs and Error Budgets

- [ ] Platform availability SLO 99.9% instrumented (NFR-021)
- [ ] API latency SLOs: p95 read ≤ 200ms, write ≤ 500ms (NFR-003, NFR-004)
- [ ] Error budget dashboard with burn-rate alerts (Chapter 02)
- [ ] Error budget policy acknowledged by engineering lead

## 3. Incident Management

- [ ] SEV1–SEV4 severity matrix published (Chapter 03)
- [ ] Incident record template in use for all SEV1/SEV2
- [ ] Kill switches tested in staging (checkout, webhooks, tenant readonly)
- [ ] SEV1 tabletop: mitigation decision ≤ 30 minutes (NFR-023)
- [ ] NDPC breach drill: notification draft ≤ 4 hours from scenario start

## 4. On-Call

- [ ] PagerDuty (or equivalent) rotation live — primary + secondary (NFR-068)
- [ ] Escalation chain includes DPO and Legal for breach scenarios (Chapter 04)
- [ ] Weekly handoff document process active
- [ ] Merchant support tickets do not page on-call unless platform SEV

## 5. Capacity and Scaling

- [ ] Capacity dashboard: CPU, DB connections, queue depth, storage (Chapter 05)
- [ ] Monthly forecast document maintained
- [ ] Per-tenant API and storage quota alerts configured
- [ ] Pre-traffic-event scale checklist (Black Friday Nigeria)

## 6. Database Operations

- [ ] Automated PostgreSQL backups every 6 hours (NFR-025)
- [ ] Quarterly restore drill: RTO ≤ 4h, RPO ≤ 6h (NFR-026, NFR-027)
- [ ] PgBouncer transaction pooling with SET LOCAL verified (ADR-005)
- [ ] Zero-downtime migration checklist enforced (NFR-076)
- [ ] Tenant export and hard-delete runbooks tested

## 7. Merchant Support

- [ ] Ticketing system with plan-based SLAs (Chapter 07)
- [ ] Minimum 10 Nigeria-specific support articles published
- [ ] Engineering escalation template required for bugs
- [ ] CSAT survey enabled on ticket close

## 8. Status Page and Communications

- [ ] status.sapphital.com live with Phase 1 components (Chapter 08)
- [ ] SEV1 first public update ≤ 15 minutes demonstrated in drill
- [ ] Maintenance announcement process: 72h notice, ≤ 2h/month (NFR-022)
- [ ] In-app incident banner functional

## 9. Postmortems

- [ ] Blameless postmortem template in repository (Chapter 09)
- [ ] 100% SEV1/SEV2 incidents receive postmortem within SLA
- [ ] Action items tracked with PM- prefix and weekly review

## 10. Database and Analytics Architecture

- [ ] RLS enabled on all tenant-scoped tables (ADR-002)
- [ ] Event outbox pattern operational for domain events (Chapter 11)
- [ ] Merchant analytics dashboard Phase 2 pipeline documented and staging-validated
- [ ] Tenant isolation suite passes on analytics read path

## 11. Runbooks

- [ ] RB-001 through RB-012 authored and linked from PagerDuty (Chapter 01)
- [ ] Paystack / Flutterwave outage runbook validated with sandbox

## 12. Cross-Volume Gates

- [ ] Volume 10 infrastructure deployed per ADR-011 (Nigeria primary)
- [ ] Volume 11 security acceptance criteria met for ops-overlapping controls
- [ ] Health endpoints `/health` and `/ready` integrated with deploy pipeline (NFR-065)

---

**Sign-off roles:** Lead Architect, Platform engineer (on-call lead), DPO (breach drill), Support lead (TBD).

---

## Phase 2 Additional Gates

- [ ] Read replica operational for analytics queries
- [ ] Business SLA credits automated for Business+ tiers
- [ ] 24×7 on-call for Marketplace tier merchants
- [ ] WhatsApp support channel for Nigeria Marketplace merchants

## Phase 3 Additional Gates

- [ ] Multi-region status components (Kenya)
- [ ] Follow-the-sun on-call option
- [ ] Enterprise ops liaison role defined
