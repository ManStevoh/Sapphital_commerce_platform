# Chapter 09: Engineering Standards Checklist

**Document ID:** SCP-IMP-021-09  
**Version:** 1.0.0  
**Status:** ✅ Active  
**Traceability:** Volume 0, Volume 3 Ch. 04, Volume 13, Volume 17, ADR-001, ADR-009  

---

## Purpose

Continuous **engineering standards** checklist applied from first commit through scale — code quality, testing, reviews, ADRs, observability, and Nigeria-specific constraints. Run this checklist on every PR and at each phase gate.

## Scope

- Repository and branch governance
- Modular monolith architecture rules
- CI/CD quality gates
- Security and tenant isolation in development
- API and database conventions
- Observability and documentation
- Release and hotfix procedures

## Out of Scope

- Sprint planning and story estimation
- Individual performance reviews

## Prerequisites

- [ ] Volume 0 engineering principles read by all engineers
- [ ] Chapter 02 §1 CI/CD pipeline scaffolded
- [ ] Module ownership matrix published in team wiki

---

## §1 Repository Standards

Per [Volume 0 — Engineering Principles](../00-meta/engineering-principles.md):

**Checklist:**

- [ ] Monorepo layout matches Chapter 02 §1 (`apps/`, `packages/`, `infra/`)
- [ ] `main` branch protected; PR required; no direct pushes
- [ ] Branch naming: `feat/`, `fix/`, `chore/`, `docs/` prefixes
- [ ] Conventional commits or structured PR titles with FR/NFR/ADR reference
- [ ] `.editorconfig` enforced across PHP, TypeScript, Markdown
- [ ] Laravel Pint + ESLint + Prettier run in CI (blocking)
- [ ] Secrets never committed; gitleaks pre-commit hook + CI history scan
- [ ] `.env.example` updated when new config keys added
- [ ] Dependency versions pinned in lockfiles; no floating major versions

---

## §2 Code Architecture

Per [Volume 3 Ch. 04](../03-architecture/04-modular-monolith-structure.md):

**Checklist:**

- [ ] Clean architecture layers: Domain → Application → Infrastructure
- [ ] No cross-module direct database access; use module APIs or domain events
- [ ] All tenant queries through RLS context middleware (`app.tenant_id`)
- [ ] Money stored as integer cents + ISO 4217 currency (never float)
- [ ] FR/NFR IDs in docblocks for new features
- [ ] ADR required for architectural changes ([Volume 0 ADR process](../00-meta/document-control.md))
- [ ] No business logic in controllers; use Actions/Services in Application layer
- [ ] Domain events emitted for state changes that affect other modules
- [ ] Eloquent models with `tenant_id` use global scope + RLS policy

---

## §3 Testing Gates (CI)

Per [Volume 13](../13-testing/README.md):

| Gate | Threshold | Blocking |
|------|-----------|----------|
| Unit test coverage (domain layer) | ≥ 80% | Yes |
| Integration tests | All public API endpoints happy path | Yes |
| Tenant isolation suite | 100% pass | Yes |
| Authz matrix test | 100% routes covered | Yes |
| E2E Playwright | Checkout, admin product, webhook | Nightly + pre-release |
| Security SAST (Semgrep) | Zero critical | Yes |
| Dependency audit | Zero critical/high CVE | Yes |
| PCI test pack | Zero failures | Yes (payments paths) |
| Lighthouse CI (storefront) | ≥ 85 mobile on PR; ≥ 90 at GA | Yes (theme changes) |

**Checklist:**

- [ ] Pest used for PHP; Vitest for TypeScript
- [ ] Test database uses PostgreSQL (not SQLite) for RLS tests
- [ ] Factory definitions include `tenant_id` on all tenant-scoped models
- [ ] Payment webhook tests include signature verification and idempotency
- [ ] Flaky test policy: quarantine within 24h; fix within 5 business days

---

## §4 Pull Request Checklist

Every PR author completes before requesting review:

- [ ] Linked issue or FR/NFR/ADR ID in PR description
- [ ] Migration included if schema change ([Volume 17 Ch. 05](../17-database-data-architecture/05-migrations-and-versioning.md))
- [ ] RLS policies added/updated if new tenant-scoped table
- [ ] OpenAPI spec updated if public API contract changed
- [ ] No `dd()`, `console.log`, or debug dumps in production paths
- [ ] No `TODO` without linked issue (zero TODOs in payment/auth paths)
- [ ] Peer review from module owner (minimum 1 approval)
- [ ] Security review required for: auth, payments, tenant isolation, encryption
- [ ] Screenshots or screen recording for UI changes
- [ ] Rollback plan noted for migrations that are not backward-compatible

---

## §5 API Standards

Per [Volume 3 Ch. 08](../03-architecture/08-api-architecture-and-versioning.md):

**Checklist:**

- [ ] REST endpoints versioned: `/api/v1/`, `/storefront/v1/`
- [ ] JSON request/response; `Content-Type: application/json`
- [ ] Pagination: cursor-based for lists > 100 items
- [ ] Error format: `{ "error": { "code", "message", "details" } }`
- [ ] Idempotency-Key header on checkout and payment endpoints
- [ ] Rate limit headers: `X-RateLimit-Limit`, `X-RateLimit-Remaining`
- [ ] 429 responses include `Retry-After`
- [ ] OpenAPI 3.1 spec is source of truth; contract tests in CI
- [ ] Breaking changes require new API version, not silent modification

---

## §6 Database Standards

Per [Volume 17](../17-database-data-architecture/README.md):

**Checklist:**

- [ ] `tenant_id UUID NOT NULL` on every tenant-scoped table from first migration
- [ ] Foreign keys indexed; composite indexes on `(tenant_id, ...)` for list queries
- [ ] Migrations backward-compatible (expand-contract pattern)
- [ ] No `DELETE` on audit log table; append-only
- [ ] PII columns use Laravel encrypted cast where specified in Volume 11
- [ ] Seed data uses realistic Nigeria addresses and NGN amounts
- [ ] Migration rollback script tested in CI for every schema change

---

## §7 Security in Development

Per [Volume 11](../11-security/README.md) and [Chapter 06](./06-phase1-security-compliance-playbook.md):

**Checklist:**

- [ ] No card input fields anywhere in codebase (automated CI scan)
- [ ] Webhook endpoints verify HMAC before state changes
- [ ] User input sanitized; HTML escaped or DOMPurify for rich text
- [ ] SQL via Eloquent/query builder only; no raw unparameterized queries
- [ ] CSRF protection on all state-changing admin forms
- [ ] File uploads: MIME validation, size limits, tenant-prefixed R2 keys
- [ ] Dependency updates reviewed weekly; critical patches within 48 hours
- [ ] Security training completed by all engineers (OWASP Top 10 2025)

---

## §8 Observability

Per [Volume 10 Ch. 08](../10-infrastructure/08-monitoring-observability.md):

**Checklist:**

- [ ] Structured JSON logs with `tenant_id`, `request_id`, `trace_id`
- [ ] Log levels: ERROR for failures, INFO for business events, DEBUG disabled in production
- [ ] No PII in logs (email/phone scrubbed)
- [ ] Metrics: request rate, error rate, latency p50/p95/p99 per route
- [ ] OpenTelemetry traces on checkout and payment paths
- [ ] Alerts on SLO burn ([Volume 14 Ch. 02](../14-operations/02-slo-error-budgets.md))
- [ ] Audit events for admin mutations per ADR-009
- [ ] Queue depth and worker utilization monitored

---

## §9 Documentation Standards

**Checklist:**

- [ ] README per module with local setup steps (no npm install in docs — reference Volume 10)
- [ ] Runbook for on-call when new critical path added ([Volume 14 Ch. 12](../14-operations/12-runbooks.md))
- [ ] Specification volume updated if implementation differs from design
- [ ] ADR written within 5 business days of architectural decision
- [ ] OpenAPI changelog maintained per API version
- [ ] Inline docblocks on public service methods

---

## §10 Nigeria-Specific Engineering Rules

**Checklist:**

- [ ] Default currency NGN; timezone Africa/Lagos in seed and provisioning
- [ ] Phone validation accepts +234 formats
- [ ] Paystack test keys never deployed to production merchant stores
- [ ] Production compute in Nigeria/West Africa per ADR-011
- [ ] NDPA data export/deletion endpoints tested on every release
- [ ] Termii SMS templates approved before production send
- [ ] VAT 7.5% calculation tested with integer cents (no rounding drift)

---

## §11 Release & Hotfix Procedures

Per [Volume 14 Ch. 03](../14-operations/03-release-management.md):

**Checklist:**

- [ ] Release branch cut from `main`; only blocker fixes merged
- [ ] Full CI suite green on release branch before deploy
- [ ] Staging deploy → smoke test → production deploy (manual approval)
- [ ] Database migrations run with rollback script ready
- [ ] Post-deploy: synthetic monitoring green within 15 minutes
- [ ] Hotfix path: branch from release tag → fix → CI → deploy → backport to `main`
- [ ] Payment hotfixes require Lead Architect + QA sign-off
- [ ] Changelog updated for every production deploy

---

## §12 Phase Gate Standards Summary

| Phase | Additional Gates Beyond §1–§11 |
|-------|-------------------------------|
| Phase 1 Nigeria GA | Isolation 0 failures; PCI pack; pen test; Lighthouse ≥ 90 |
| Phase 2 Growth | Outbox tests; AI PII scrubbing; zero-downtime deploy proven |
| Phase 3 Platform | Vendor isolation suite; OAuth scope tests; payout reconciliation |

---

## §13 Engineering Standards — Master Checklist

| # | Domain | Apply From | Status |
|---|--------|------------|--------|
| 1 | Repository governance | Commit 1 | ☐ |
| 2 | Modular monolith boundaries | Week 1 | ☐ |
| 3 | CI testing gates | Week 2 | ☐ |
| 4 | PR checklist enforced | Week 2 | ☐ |
| 5 | API conventions | Week 4 | ☐ |
| 6 | Database + RLS standards | Week 2 | ☐ |
| 7 | Security dev practices | Week 1 | ☐ |
| 8 | Observability | Week 5 | ☐ |
| 9 | Documentation | Continuous | ☐ |
| 10 | Nigeria-specific rules | Week 4 | ☐ |
| 11 | Release procedures | Pre-staging | ☐ |

---

## Dependencies

| Volume | Usage |
|--------|-------|
| [Volume 0](../00-meta/engineering-principles.md) | Engineering principles |
| [Volume 3 Ch. 04](../03-architecture/04-modular-monolith-structure.md) | Architecture layers |
| [Volume 13](../13-testing/README.md) | Testing strategy |
| [Volume 17](../17-database-data-architecture/README.md) | Database conventions |
| [Volume 14](../14-operations/README.md) | Release management |

---

## References

- [Volume 0 — Engineering Principles](../00-meta/engineering-principles.md)
- [Volume 13 Ch. 04 — Tenant Isolation Tests](../13-testing/04-tenant-isolation-test-suite.md)
- [Volume 13 Ch. 07 — Security Testing](../13-testing/07-security-testing.md)
- [ADR-009 — Audit Log Immutability](../00-meta/adr/009-audit-log-immutability.md)
