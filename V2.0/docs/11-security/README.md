# Volume 11: Security & Compliance

**Document ID:** SCP-SEC-001  
**Version:** 1.0.0  
**Status:** 📝 Draft  
**Depends On:** Volume 3 (Architecture), ADR-001 through ADR-011  
**Owner:** Sapphital Learning Company  

---

## Purpose

This volume defines how SCP protects merchants, customers, and platform operations across **Nigeria (primary market)**, **Kenya and East Africa**, and **broader African expansion**. Security is not a Phase 2 afterthought — it is a launch requirement tied to regulatory registration, payment compliance, and merchant trust.

## Scope

- Compliance matrices (OWASP, PCI, Nigeria NDPA, Kenya DPA, GDPR readiness)
- Threat modeling (STRIDE)
- Security architecture (authn, authz, encryption, edge, audit)
- Africa-specific regulatory implementation
- Per-module security checklists
- Security testing and acceptance criteria
- Incident response

## Out of Scope

- Detailed infrastructure runbooks (Volume 10)
- Payment provider API integration specs (Volume 5)
- Legal contract text (legal counsel owns Terms/DPA)

## Chapters

| # | Chapter | Status |
|---|---------|--------|
| 01 | [Compliance Framework](./01-compliance-framework.md) | 📝 Draft |
| 02 | [Africa Regulatory Compliance](./02-africa-regulatory-compliance.md) | 📝 Draft |
| 03 | [Threat Model](./03-threat-model.md) | 📝 Draft |
| 04 | [Security Architecture](./04-security-architecture.md) | 📝 Draft |
| 05 | [Security Testing](./05-security-testing.md) | 📝 Draft |
| 06 | [Incident Response](./06-incident-response.md) | 📝 Draft |
| 07 | [Acceptance Criteria](./07-acceptance-criteria.md) | 📝 Draft |

## Standards Baseline (July 2026)

| Standard | Version | SCP Target |
|----------|---------|------------|
| OWASP ASVS | **5.0.0** (May 2025) | Level 2 (NFR-029) |
| OWASP Top 10 | **2025** | Full control mapping |
| PCI DSS | v4.0.1 SAQ A r1 | Hosted/redirect checkout (ADR-004) |
| Nigeria NDPA | 2023 + GAID 2025 | Full compliance at Nigeria launch (NFR-078) |
| Kenya DPA | 2019 + ODPC guidance | At Kenya launch (NFR-079) |
| WCAG | 2.2 AA | Volume 4 (SDS) |
| GDPR | 2016/679 | Readiness Phase 3 (NFR-072) |

## Related ADRs

- [ADR-004](../00-meta/adr/004-checkout-psp-redirect-saq-a.md) — Checkout / PCI SAQ A
- [ADR-005](../00-meta/adr/005-rls-pgbouncer-set-local.md) — RLS + PgBouncer
- [ADR-006](../00-meta/adr/006-authentication-stack.md) — Authentication
- [ADR-007](../00-meta/adr/007-secrets-management.md) — Secrets
- [ADR-008](../00-meta/adr/008-edge-security-cloudflare.md) — Edge security
- [ADR-009](../00-meta/adr/009-audit-log-immutability.md) — Audit logs
- [ADR-010](../00-meta/adr/010-admin-impersonation.md) — Impersonation
- [ADR-011](../00-meta/adr/011-data-residency-africa.md) — Data residency
