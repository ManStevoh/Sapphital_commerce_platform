# Chapter 02: Phase 1 — Foundation Playbook

**Document ID:** SCP-IMP-021-02  
**Version:** 1.0.0  
**Status:** ✅ Active  
**Traceability:** ADR-001, ADR-002, ADR-005, ADR-006, ADR-007, ADR-011, NFR-040, PRD-003  

---

## Purpose

Step-by-step build sequence for SCP **platform foundation** — repository structure, infrastructure, multi-tenancy, authentication, tenant lifecycle, and SaaS billing — everything required before commerce modules ship.

## Scope

- Monorepo layout and modular monolith boundaries
- Phase 1 Docker Compose production topology
- PostgreSQL with RLS and PgBouncer
- Authentication and authorization guards
- Tenant provisioning and SaaS subscription layer
- CI/CD pipeline and environment configuration

## Out of Scope

- Product catalog and checkout (Chapter 03)
- Storefront rendering (Chapter 04)
- NDPA registration workflow detail (Chapter 06)

## Prerequisites

- [ ] Volume 3 architecture reviewed by lead engineer
- [ ] ADRs 001–011 approved
- [ ] Cloudflare account, Lagos-region VM, and secrets vault provisioned ([Volume 10 Ch. 02](../10-infrastructure/02-cloud-architecture-nigeria-africa.md))
- [ ] GitHub organization and branch protection policy defined

---

## §1 Repository & CI/CD (Weeks 1–2)

### 1.1 Monorepo Structure

Create the implementation repository with these top-level modules:

```text
scp/
├── apps/
│   ├── api/          # Laravel 12 modular monolith
│   └── storefront/   # Next.js 15 App Router
├── packages/
│   ├── commerce/     # Domain modules (catalog, orders, payments)
│   ├── platform/     # Tenancy, auth, billing
│   ├── shared/       # DTOs, events, contracts
│   └── theme-sdk/    # Theme schema types
├── infra/
│   ├── docker/       # Compose files Phase 1–3
│   └── terraform/    # Cloud provisioning (optional Phase 1)
└── docs/             # OpenAPI specs, runbooks
```

**Checklist:**

- [ ] Laravel 12 app with FrankenPHP Octane worker configuration per [Volume 10 Ch. 03](../10-infrastructure/03-compute-frankenphp-octane.md)
- [ ] Next.js 15 storefront app with App Router and edge-compatible middleware
- [ ] Shared `packages/` published via Composer path repos and npm workspaces
- [ ] `.env.example` with all required keys documented; no secrets committed
- [ ] ADR template in `docs/adr/` linked to Volume 0 convention

### 1.2 CI/CD Pipeline

Implement pipeline per [Volume 10 Ch. 06](../10-infrastructure/06-ci-cd-pipeline.md):

| Stage | Actions | Blocking |
|-------|---------|----------|
| Lint | Pint, ESLint, Prettier | Yes |
| Unit | Pest (api), Vitest (storefront) | Yes |
| Integration | Pest + PostgreSQL service container | Yes |
| Tenant isolation | Auto-generated Pest suite | Yes |
| Security | gitleaks, Semgrep, dependency audit | Yes |
| Build | Docker image build (no deploy) | Yes |
| E2E | Playwright (nightly + pre-release) | Nightly |

**Checklist:**

- [ ] `main` branch requires 1 approval + all blocking stages green
- [ ] Staging deploy on merge to `main`; production deploy manual approval
- [ ] Database migrations run automatically in deploy job with rollback script
- [ ] SBOM artifact generated per build ([Volume 11 Ch. 07](../11-security/07-acceptance-criteria.md) §10)
- [ ] Secrets injected from vault per [ADR-007](../00-meta/adr/007-secrets-management.md)

### 1.3 Environments

| Environment | Purpose | Data | URL Pattern |
|-------------|---------|------|-------------|
| Local | Developer machines | Seed data | `*.scp.test` |
| CI | Automated tests | Ephemeral PostgreSQL | N/A |
| Staging | Pre-production validation | Anonymized copy | `*.staging.sapphital.com` |
| Production | Nigeria GA | Live merchant data | `*.sapphital.com` + custom domains |

**Checklist:**

- [ ] Staging mirrors production topology (single VM Compose Phase 1)
- [ ] Production compute in Nigeria/West Africa per ADR-011
- [ ] Cloudflare proxy enabled on all public environments
- [ ] Config diff review required for production env changes

**Gate §1:** Empty Laravel health endpoint and Next.js homepage deploy to staging with CI fully green.

---

## §2 Multi-Tenancy & Data Layer (Weeks 2–4)

### 2.1 PostgreSQL Schema Foundation

Implement per [Volume 3 Ch. 05](../03-architecture/05-multi-tenancy-and-isolation.md) and [Volume 16 Ch. 02](../16-saas-multi-tenancy/02-tenant-lifecycle.md):

**Checklist:**

- [ ] `tenants` table with `id`, `slug`, `status`, `plan_id`, `country`, `created_at`
- [ ] `tenant_id UUID NOT NULL` on every tenant-scoped table from first migration
- [ ] PostgreSQL RLS policies: `tenant_id = current_setting('app.tenant_id')::uuid`
- [ ] PgBouncer transaction pooling with `SET LOCAL app.tenant_id` per ADR-005
- [ ] Middleware sets tenant context from subdomain, custom domain, or API token
- [ ] Platform admin routes use separate guard; cannot inherit merchant tenant context
- [ ] Redis key prefix: `{tenant_id}:` on all cache entries
- [ ] Meilisearch index per tenant: `products_{tenant_id}` ([Volume 10 Ch. 04](../10-infrastructure/04-postgresql-redis-meilisearch.md))

### 2.2 Tenant Isolation Test Suite

Build auto-generated suite per [Volume 13 Ch. 04](../13-testing/04-tenant-isolation-test-suite.md):

- [ ] Generator scans all Eloquent models with `tenant_id` column
- [ ] For each model: create two tenants, assert Tenant A cannot read/write Tenant B
- [ ] Direct SQL test bypassing Eloquent confirms RLS enforcement
- [ ] Cache, queue, and file storage isolation tests included
- [ ] Suite runs on every PR; 0 failures required to merge

**Gate §2:** Isolation suite passes with ≥ 20 tenant-scoped models scaffolded (tenants, users, stores, settings).

---

## §3 Authentication & Authorization (Weeks 3–5)

Implement per [ADR-006](../00-meta/adr/006-authentication-stack.md) and [Volume 3 Ch. 06](../03-architecture/06-request-lifecycle-and-auth.md):

### 3.1 Auth Surfaces

| Surface | Guard | Mechanism | MFA |
|---------|-------|-----------|-----|
| Merchant admin | `merchant` | Session + Sanctum SPA | Phase 2 merchant owners |
| Platform admin | `platform` | Separate session domain | Mandatory Phase 1 |
| Storefront customer | `customer` | Optional account; guest checkout | N/A |
| REST API | `api` | Bearer token (Sanctum PAT) | N/A |
| Webhooks inbound | HMAC | Provider signature | N/A |

**Checklist:**

- [ ] Separate user tables: `merchant_users`, `platform_admins`, `customers`
- [ ] RBAC roles: Owner, Admin, Staff, Finance per store ([Volume 1 Ch. 08](../01-vision/08-user-roles-and-permissions.md))
- [ ] Permission middleware on every admin and API route; authz matrix test covers all routes
- [ ] TOTP MFA enforced for platform admins; backup codes generated
- [ ] Password policy: ≥ 12 chars, breach list check (HaveIBeenPwned k-anonymity API)
- [ ] Rate limiting: 5 login failures → 15-minute lockout per IP + account
- [ ] Session fixation protection; secure, HttpOnly, SameSite cookies
- [ ] API tokens scoped per store with explicit permission list
- [ ] Audit events: `auth.login`, `auth.logout`, `auth.mfa`, `auth.failed` per ADR-009

### 3.2 Platform Admin Impersonation

Per [ADR-010](../00-meta/adr/010-admin-impersonation.md):

- [ ] Impersonation requires MFA re-verification
- [ ] Banner displayed in impersonated session
- [ ] Auto-expire after 2 hours
- [ ] Audit log: `impersonation.start`, `impersonation.end` with actor and target tenant

**Gate §3:** Merchant signup → login → RBAC-protected admin dashboard accessible; platform admin MFA login verified.

---

## §4 Tenant Lifecycle & SaaS Billing (Weeks 4–6)

Implement per [Volume 16](../16-saas-multi-tenancy/README.md):

### 4.1 Signup & Provisioning

**Checklist:**

- [ ] `POST /api/v1/signup` creates tenant in `provisioning` state
- [ ] `ProvisionTenant` job completes within 60 seconds p95:
  - [ ] Default store with NGN currency, Africa/Lagos timezone
  - [ ] Recommended launch theme assigned from merchant vertical
  - [ ] 3 sample products seeded (deletable)
  - [ ] About and Contact pages created
  - [ ] Paystack test mode placeholder configured
  - [ ] R2 prefix `{tenant_id}/` created
- [ ] Email verification required before trial activation
- [ ] Cloudflare Turnstile on signup form ([Volume 11](../11-security/04-security-architecture.md))

### 4.2 Plans & Billing

Per [Volume 16 Ch. 03–04](../16-saas-multi-tenancy/03-plans-and-entitlements.md):

| Plan | Price (NGN/mo) | Products | Staff | Custom Domain |
|------|----------------|----------|-------|---------------|
| Starter | ₦15,000 | 100 | 2 | No |
| Growth | ₦45,000 | 1,000 | 10 | Yes |
| Pro | ₦120,000 | Unlimited | 25 | Yes |

**Checklist:**

- [ ] Plan entitlements enforced at API layer (not UI-only)
- [ ] Trial period: 14 days on Starter features
- [ ] Subscription billing via Paystack recurring (platform billing, separate from merchant PSP keys)
- [ ] Invoice generation with VAT display per Nigeria tax rules
- [ ] State machine: `provisioning → trial → active → past_due → suspended → churned → deleted`
- [ ] Suspension blocks storefront within 60 seconds (cache purge)
- [ ] NDPA deletion workflow: export within 48h; hard delete at 90 days

### 4.3 Custom Domains (Growth Plan)

Defer full implementation to Phase 2; scaffold in Phase 1:

- [ ] Database schema for `custom_domains` with verification token
- [ ] Admin UI shows "Upgrade to Growth" for custom domain feature

**Gate §4:** End-to-end signup → provisioned store → trial active → admin onboarding redirect in staging.

---

## §5 Observability & Platform Services (Weeks 5–6)

Per [Volume 10 Ch. 08](../10-infrastructure/08-monitoring-observability.md):

**Checklist:**

- [ ] Structured JSON logging with `tenant_id`, `request_id`, `user_id` on every line
- [ ] OpenTelemetry traces exported to collector
- [ ] Metrics: request latency histogram, queue depth, Octane worker utilization
- [ ] Alerting: PagerDuty/Opsgenie for SEV1 (checkout down, DB unreachable)
- [ ] SLO dashboards: availability, LCP, API p95
- [ ] Audit log table append-only; no UPDATE/DELETE grants per ADR-009
- [ ] Notification service abstraction: email (Postmark/Resend), SMS (Termii)
- [ ] File storage via Cloudflare R2 with tenant-prefixed keys

---

## §6 Phase 1 Foundation — Complete Checklist

| # | Item | Owner | Verified |
|---|------|-------|----------|
| 1 | Monorepo with api + storefront apps | Platform Eng | ☐ |
| 2 | CI pipeline all blocking gates green | DevOps | ☐ |
| 3 | Staging deploy automated | DevOps | ☐ |
| 4 | PostgreSQL RLS on all tenant tables | Backend | ☐ |
| 5 | Tenant isolation suite 0 failures | QA | ☐ |
| 6 | Merchant auth + RBAC | Backend | ☐ |
| 7 | Platform admin MFA + impersonation audit | Security | ☐ |
| 8 | Tenant provisioning ≤ 60s p95 | Backend | ☐ |
| 9 | SaaS plans + trial billing | Backend | ☐ |
| 10 | Structured logging + tracing | DevOps | ☐ |
| 11 | R2 file storage with tenant prefix | Backend | ☐ |
| 12 | Cloudflare WAF + rate limits active | DevOps | ☐ |

---

## Dependencies

| Upstream | Usage |
|----------|-------|
| [Volume 3](../03-architecture/README.md) | Modular monolith boundaries, auth lifecycle |
| [Volume 10](../10-infrastructure/README.md) | Compute, data layer, CI/CD, monitoring |
| [Volume 16](../16-saas-multi-tenancy/README.md) | Tenant lifecycle, plans, billing |
| [Volume 13 Ch. 04](../13-testing/04-tenant-isolation-test-suite.md) | Isolation test generator |
| [Volume 11](../11-security/README.md) | Auth security controls |
| Research Track 19 | Documentation and ADR governance |

## Next Chapter

Proceed to [Chapter 03 — Commerce Core Playbook](./03-phase1-commerce-core-playbook.md) once Gate §2 (tenant isolation) passes.

---

## References

- [Volume 3 Ch. 12 — Deployment Topology](../03-architecture/12-deployment-and-runtime-topology.md)
- [Volume 10 Ch. 06 — CI/CD Pipeline](../10-infrastructure/06-ci-cd-pipeline.md)
- [Volume 16 Ch. 02 — Tenant Lifecycle](../16-saas-multi-tenancy/02-tenant-lifecycle.md)
