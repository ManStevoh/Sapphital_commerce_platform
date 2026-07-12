# Volume 13: Testing & Quality Engineering

**Document ID:** SCP-TEST-001  
**Version:** 1.0.0  
**Status:** ✅ Active  
**Depends On:** Volume 3 (Architecture), Volume 5 (Commerce), Volume 11 (Security)  
**Traceability:** NFR-001 – NFR-012, NFR-040, NFR-042, NFR-044, NFR-047 – NFR-053  

---

## Purpose

Volume 13 defines how SCP proves correctness, security, performance, accessibility, and release readiness across a multi-tenant Commerce Operating System serving **Nigeria first**, then Kenya and wider Africa.

Quality is not a phase — it is a **continuous verification system** embedded in every pull request, nightly pipeline, and release gate.

## Chapter Index

| # | Chapter | Document ID | Status |
|---|---------|-------------|--------|
| 01 | [Testing Strategy Overview](./01-testing-strategy-overview.md) | SCP-TEST-001-01 | ✅ Active |
| 02 | [Testing Pyramid](./02-testing-pyramid.md) | SCP-TEST-001-02 | ✅ Active |
| 03 | [Unit & Integration Tests (Pest, Vitest)](./03-unit-integration-tests.md) | SCP-TEST-001-03 | ✅ Active |
| 04 | [Tenant Isolation Test Suite](./04-tenant-isolation-test-suite.md) | SCP-TEST-001-04 | ✅ Active |
| 05 | [End-to-End Testing (Playwright)](./05-e2e-playwright.md) | SCP-TEST-001-05 | ✅ Active |
| 06 | [Performance Testing (k6, Lighthouse)](./06-performance-k6-lighthouse.md) | SCP-TEST-001-06 | ✅ Active |
| 07 | [Security Testing](./07-security-testing.md) | SCP-TEST-001-07 | ✅ Active |
| 08 | [Accessibility Testing](./08-accessibility-testing.md) | SCP-TEST-001-08 | ✅ Active |
| 09 | [CI Quality Gates](./09-ci-quality-gates.md) | SCP-TEST-001-09 | ✅ Active |
| 10 | [Release Criteria](./10-release-criteria.md) | SCP-TEST-001-10 | ✅ Active |

## Toolchain Summary

| Layer | Primary Tools | Gate |
|-------|---------------|------|
| Backend unit/feature | **Pest** (Laravel 12) | PR merge |
| Frontend unit/component | **Vitest** + Testing Library | PR merge |
| Integration | Pest + HTTP client + PostgreSQL test DB | PR merge |
| **Tenant isolation** | Auto-generated Pest suite | **Blocking PR** |
| E2E | **Playwright** (Chromium, WebKit, mobile) | Nightly + pre-release |
| Load/performance | **k6** | Weekly staging + pre-release |
| Web vitals / a11y audit | **Lighthouse CI** | PR (changed routes) + nightly |
| Security | gitleaks, Semgrep, OWASP ZAP, PCI test pack | PR + weekly |
| Accessibility | axe-core, Playwright a11y, manual NVDA/VoiceOver | PR + release |

## Cross-Volume References

- **NFR-040** — Tenant isolation verified by automated suite (Chapter 04)
- **NFR-044** — PCI SAQ A compliance tests (Chapters 07, 10)
- **NFR-047 – NFR-053** — WCAG 2.2 AA accessibility (Chapter 08)
- **ADR-002** — RLS + application scoping; isolation suite validates both layers
- **ADR-004** — PSP redirect checkout; PCI test pack validates no card data on platform
- **Volume 11 Ch 05** — Security testing overlap; Volume 13 owns execution standards

## Nigeria & Africa Context

SCP handles Nigerian consumer PII, NDPA subject rights, and CBN-regulated payment flows. A single cross-tenant leak or payment state bug is a **regulatory and reputational incident**, not a minor defect. The tenant isolation suite and PCI compliance pack are **launch blockers** for Nigeria GA.

## Quality Philosophy

1. **Shift left** — Catch defects at the cheapest layer (unit before E2E).
2. **Fail closed** — Missing tenant context, auth, or payment verification must fail tests, not warn.
3. **Deterministic CI** — No flaky tests in merge gates; quarantine with SLA.
4. **Evidence for compliance** — Test reports, coverage, and scan artifacts retained per release.
5. **Builder-friendly** — Tests document behavior; engineers run the full PR suite locally in &lt; 10 minutes.

---

**Owner:** Engineering Quality Lead (TBD)  
**Review cycle:** Quarterly alignment with Volume 11 security acceptance criteria
