# Chapter 12: Launch Readiness Checklist

**Document ID:** SCP-IMP-021-12  
**Version:** 1.0.0  
**Status:** ✅ Active  
**Traceability:** NFR-021, NFR-044, NFR-078, NFR-083, Volume 11, Volume 13 Ch. 10, Volume 14, Volume 16  

---

## Purpose

**Nigeria GA go/no-go checklist** — the definitive gate before public launch. Every item must be checked, evidenced, and signed off. No exceptions without written risk acceptance from CEO and DPO.

## Scope

- Engineering readiness
- Security and compliance blockers
- Payment verification
- Performance and reliability
- Product and merchant readiness
- Operations and support
- Legal and regulatory
- Launch day runbook

## Out of Scope

- Kenya corridor launch (separate regional pack in Volume 13 Ch. 10)
- Marketing launch campaign

## Prerequisites

- [ ] Chapters 02–06 complete (Phase 1 playbooks)
- [ ] Chapter 09 standards enforced for 4+ weeks
- [ ] Chapter 10 merchant and shopper journeys verified
- [ ] Staging soak ≥ 48 hours with 0 SEV1/SEV2 incidents

---

## §1 Go/No-Go Decision Framework

| Decision | Criteria | Authority |
|----------|----------|-----------|
| **GO** | 100% launch blockers checked; ≤ 2 non-blockers open with mitigation | CEO + Lead Architect |
| **CONDITIONAL GO** | 1 blocker with written risk acceptance + 72h remediation plan | CEO + DPO + Lead Architect |
| **NO-GO** | Any unchecked blocker | Automatic |

**Launch blockers** are marked 🔴 below. Non-blockers are marked 🟡.

---

## §2 Engineering Readiness

### 2.1 Code & Deploy 🔴

- [ ] Release branch `release/v1.0.0-nigeria-ga` created from `main`
- [ ] 3 consecutive nightly pipelines green on release branch
- [ ] All PR merge gates green (lint, unit, integration, isolation, security)
- [ ] Changelog published for v1.0.0
- [ ] Database migrations tested: fresh install + upgrade from beta
- [ ] Rollback procedure (RB-002) validated in staging within last 7 days
- [ ] Feature flags default-safe; beta flags disabled in production
- [ ] No P0 or P1 open bugs in release scope

### 2.2 Architecture 🔴

- [ ] Modular monolith boundaries verified by architecture test
- [ ] 100% tenant-scoped models in isolation test suite — 0 failures
- [ ] Authz matrix test covers 100% of routes — 0 failures
- [ ] No `tenant_id` nullable on any tenant-scoped table
- [ ] OpenAPI 3.1 spec published for Storefront and Admin APIs

### 2.3 Data 🟡

- [ ] Production PostgreSQL backup verified (restore test within 30 days)
- [ ] R2 bucket lifecycle policies configured
- [ ] Meilisearch index rebuild procedure documented and tested

---

## §3 Security & Compliance — Launch Blockers 🔴

Consolidated from [Chapter 06](./06-phase1-security-compliance-playbook.md) and [Volume 11 Ch. 07](../11-security/07-acceptance-criteria.md):

### 3.1 OWASP ASVS 5.0

- [ ] 100% Level 1 requirements verified with evidence
- [ ] ≥ 95% Level 2 requirements verified (remainder have risk acceptance ADR)
- [ ] OWASP ZAP baseline scan: 0 high findings on production-config staging
- [ ] External penetration test completed; 0 critical, 0 high open findings

### 3.2 Nigeria NDPA / GAID (NFR-083)

- [ ] NDPC registration complete (DCPMI tier confirmed by legal)
- [ ] NDPC-certified DPO appointed and documented in RoPA
- [ ] Privacy Policy published at `sapphital.com/privacy`
- [ ] Terms of Service published at `sapphital.com/terms`
- [ ] Data Processing Agreement annex available for merchants
- [ ] Subprocessor register published with transfer mechanisms
- [ ] RoPA current and reviewed by DPO within last 30 days
- [ ] Cross-border transfer register complete for all Phase 1 subprocessors
- [ ] Data export demonstrated end-to-end (request → JSON/CSV within 48h)
- [ ] Data deletion demonstrated end-to-end (request → hard delete at 90 days)
- [ ] Breach runbook tested; simulated NDPC notification within 72h workflow
- [ ] Primary production infrastructure in Nigeria/West Africa (ADR-011)

### 3.3 PCI DSS SAQ A (NFR-044)

- [ ] Redirect/hosted checkout confirmed — no card input fields in SCP
- [ ] PCI test pack in CI: 0 failures on release branch
- [ ] SAQ A r1 questionnaire completed with evidence
- [ ] Attestation of Compliance (AoC) signed
- [ ] First quarterly ASV scan scheduled

### 3.4 Authentication & Edge

- [ ] MFA enforced for all platform admin accounts
- [ ] TLS 1.3; SSL Labs grade A+ on production domain
- [ ] Cloudflare WAF in blocking mode
- [ ] Turnstile active on signup, login, checkout
- [ ] Rate limits verified: 429 with `Retry-After` header
- [ ] Zero secrets in repository history (gitleaks verified)

---

## §4 Payments — Launch Blockers 🔴

From [Chapter 05](./05-phase1-payments-nigeria-playbook.md):

- [ ] Paystack **live mode** checkout: end-to-end test transaction → paid order
- [ ] Paystack webhook: `charge.success` processed within 5 seconds
- [ ] Flutterwave live mode verified (if enabled for GA)
- [ ] Webhook signature verification tested with invalid signature (rejected)
- [ ] Amount mismatch webhook rejected (never marks paid)
- [ ] Idempotent webhook: duplicate delivery handled correctly
- [ ] Missed webhook recovery job tested (poll after 15 min)
- [ ] Refund: full refund on live test order processed successfully
- [ ] Platform SaaS billing: trial → paid subscription cycle verified
- [ ] Reconciliation job: nightly report generated with 0 unexplained discrepancies
- [ ] Bank transfer auto-expire at 72h verified
- [ ] No PAN, CVV, or expiry in database, logs, or error reports

---

## §5 Performance & Reliability — Launch Blockers 🔴

From [Volume 10](../10-infrastructure/README.md) SLOs:

### 5.1 Load Testing

- [ ] k6 load test: 500 concurrent shoppers sustained 15 minutes
- [ ] API read p95 ≤ 200ms under load
- [ ] API write p95 ≤ 500ms under load
- [ ] Checkout completion rate ≥ 95% under load (no timeout failures)
- [ ] Background job lag p95 ≤ 5 seconds under load

### 5.2 Web Performance

- [ ] Lighthouse mobile ≥ 90 on Lagos Atelier, Savanna Market, and Terminal Tech reference product pages
- [ ] LCP p75 ≤ 2.0s on production-config staging
- [ ] Storefront LCP monitored via RUM (real user monitoring)

### 5.3 Reliability

- [ ] 99.9% availability sustained on staging over 30-day window
- [ ] DR restore drill completed within RTO 4 hours (last 30 days)
- [ ] RPO verified ≤ 6 hours (backup frequency)
- [ ] External synthetic monitoring live from Nigeria probe:
  - [ ] Homepage check every 5 minutes
  - [ ] Checkout initialization every 15 minutes
  - [ ] API health every 1 minute

---

## §6 Product Readiness — Launch Blockers 🔴

From [Chapter 10](./10-onboarding-user-journeys.md):

### 6.1 Merchant Journey (AI-Guided — ADR-021)

- [ ] TPE provisions store ≤ 60s p95; progress UI during async jobs (ADR-022)
- [ ] Wildcard subdomain `*.shops.sapphital.africa` resolves to correct store
- [ ] AI business interview configures country, currency, tax, theme draft
- [ ] Readiness score service functional; launch blocked below threshold
- [ ] Starter flow: interview → payments → product → launch in ≤ 45 minutes (usability n≥5, ≥ 80% success)
- [ ] Paystack connect flow: test + live mode
- [ ] Kenya path: M-Pesa recommended in interview (Phase 1b gate)
- [ ] Privacy policy template publishable
- [ ] Post-launch Copilot briefing on admin home (Intelligence Platform)
- [ ] Pre-signup AI assistant (🟡 non-blocker Phase 1; blocker Phase 1.5)

### 6.2 Shopper Journey

- [ ] Guest checkout: browse → cart → checkout → Paystack → confirmation
- [ ] Playwright E2E: all 5 shopper scenarios green on release branch
- [ ] Order confirmation email received within 2 minutes
- [ ] Mobile checkout verified on 375px viewport

### 6.3 Admin

- [ ] Order management: view, process, ship, refund
- [ ] Product CRUD with variants and images
- [ ] Shipping zone configuration (Nigeria)
- [ ] Coupon creation and redemption
- [ ] Basic analytics dashboard (orders, revenue)

---

## §7 SaaS & Billing — Launch Blockers 🔴

From [Volume 16](../16-saas-multi-tenancy/README.md) and [Ch. 11–12](../16-saas-multi-tenancy/11-platform-admin-operator-guide.md):

- [ ] Starter, Growth, Pro plans configured with correct entitlements
- [ ] 14-day trial activates on signup
- [ ] Trial expiry → payment prompt → active subscription
- [ ] Failed payment → past_due → suspend after 14 days
- [ ] Plan upgrade/downgrade functional
- [ ] Invoice PDF generated with VAT
- [ ] Pricing page matches plan configuration
- [ ] E2E: marketing signup → FSL checkout → TPE → live store (Ch. 12)
- [ ] Platform Admin: tenant suspend/reactivate, failed provisioning retry (Ch. 11)
- [ ] Platform plan coupons apply at checkout (Ch. 13)

### Explicitly not required at Nigeria GA 🟢

- [ ] ~~Buyer wallet for SaaS billing~~ — FSL redirect only (Vol 16 Ch. 04, capability matrix)
- [ ] ~~Standalone Service listings module~~ — Phase 3 Bookings extension (Vol 5 Ch. 22)
- [ ] ~~Legacy license/update wizard~~ — CI/CD + Module Manager

---

## §8 Operations & Support

### 8.1 Launch Blockers 🔴

- [ ] On-call rotation live with primary and secondary
- [ ] PagerDuty/Opsgenie integrated; test alert received by on-call
- [ ] Runbooks published: RB-001 deploy, RB-002 rollback, RB-003 DB restore, RB-004 webhook backlog
- [ ] Status page live at `status.sapphital.com`
- [ ] Support email `support@sapphital.com` monitored
- [ ] Support macros for top 10 merchant issues prepared

### 8.2 Non-Blockers 🟡

- [ ] Intercom/Freshdesk ticket system integrated
- [ ] Merchant help center with 20+ articles
- [ ] In-app chat widget
- [ ] Phone support line

---

## §9 Legal & Business

### 9.1 Launch Blockers 🔴

- [ ] Merchant agreement (Terms + DPA) accepted at signup
- [ ] Refund policy template available for merchants
- [ ] CAC registration for Sapphital Learning Company verified
- [ ] Business bank account for platform SaaS billing active
- [ ] Paystack platform account verified for SaaS subscription charges

### 9.2 Non-Blockers 🟡

- [ ] Insurance (cyber liability) policy active
- [ ] Merchant success playbook documented
- [ ] Partner/reseller agreement template

---

## §10 Launch Day Runbook

### T-7 Days

- [ ] Release branch frozen; only blocker fixes allowed
- [ ] Final penetration test report reviewed
- [ ] Load test repeated on release branch
- [ ] All stakeholders notified of launch date

### T-3 Days

- [ ] Production deploy of release branch to staging for final validation
- [ ] Full E2E suite green on staging
- [ ] Go/no-go meeting scheduled

### T-1 Day

- [ ] Go/no-go meeting: review this checklist
- [ ] Production deploy during low-traffic window (02:00–04:00 WAT)
- [ ] Smoke test on production: signup, product, checkout (test mode)
- [ ] Switch Paystack to live mode for production stores
- [ ] Enable synthetic monitoring on production

### T-0 (Launch Day)

- [ ] Confirm production health: all probes green
- [ ] Remove "beta" banner; enable public signup
- [ ] Publish launch blog post / announcement
- [ ] Monitor dashboards for 8 hours (war room or dedicated channel)
- [ ] On-call engineer available for 72 hours post-launch

### T+1 Day

- [ ] Review overnight metrics: signups, errors, checkout completion
- [ ] Post-launch retrospective scheduled (within 5 business days)
- [ ] Incident log reviewed; any SEV2+ documented with post-mortem

### Rollback Criteria (Post-Launch)

Execute RB-002 rollback if any of:

- [ ] Checkout success rate < 80% for 30 minutes
- [ ] Cross-tenant data exposure confirmed
- [ ] Payment double-charge confirmed
- [ ] SEV1 incident unresolved after 60 minutes
- [ ] NDPA breach confirmed

---

## §11 Sign-Off Sheet

| Role | Name | Signature | Date | Status |
|------|------|-----------|------|--------|
| CEO / Founder | | | | ☐ GO / ☐ NO-GO |
| Lead Architect | | | | ☐ GO / ☐ NO-GO |
| DPO | | | | ☐ GO / ☐ NO-GO |
| Legal Counsel | | | | ☐ GO / ☐ NO-GO |
| QA Lead | | | | ☐ GO / ☐ NO-GO |
| DevOps Lead | | | | ☐ GO / ☐ NO-GO |
| Product Manager | | | | ☐ GO / ☐ NO-GO |

**Launch decision:** ☐ GO  ☐ NO-GO  ☐ CONDITIONAL GO

**Conditional GO conditions (if applicable):**

```text
[Document specific blockers, remediation plan, and deadline]
```

---

## §12 Evidence Package

Archive the following artifacts with the release tag:

- [ ] Penetration test report (PDF)
- [ ] PCI SAQ A AoC (PDF)
- [ ] NDPC registration confirmation
- [ ] k6 load test results (HTML report)
- [ ] Lighthouse CI report (JSON + HTML)
- [ ] Tenant isolation test report (CI artifact)
- [ ] OWASP ZAP scan report
- [ ] Playwright E2E report
- [ ] DR drill report with timestamps
- [ ] Signed go/no-go sheet (scan)
- [ ] SBOM for release artifact

Retention: 7 years for compliance artifacts; 1 year for test reports.

---

## §13 Master Launch Checklist Summary

| Category | Blockers | Checked |
|----------|----------|---------|
| Engineering | 11 | ☐ / 11 |
| Security & Compliance | 24 | ☐ / 24 |
| Payments | 12 | ☐ / 12 |
| Performance & Reliability | 12 | ☐ / 12 |
| Product | 14 | ☐ / 14 |
| SaaS & Billing | 10 | ☐ / 10 |
| Operations | 6 | ☐ / 6 |
| Legal & Business | 5 | ☐ / 5 |
| **Total Launch Blockers** | **94** | ☐ / 94 |

**Launch is authorized when all 94 launch blockers are checked and signed off.**

---

## Dependencies

| Volume | Usage |
|--------|-------|
| [Volume 11 Ch. 07](../11-security/07-acceptance-criteria.md) | Security acceptance |
| [Volume 13 Ch. 10](../13-testing/10-release-criteria.md) | Release criteria |
| [Volume 14](../14-operations/README.md) | Runbooks and on-call |
| [Volume 16 Ch. 08](../16-saas-multi-tenancy/08-saas-acceptance-criteria.md) | SaaS acceptance |
| Chapters 02–06, 09–10 | Phase 1 implementation |

---

## References

- [Volume 13 Ch. 10 — Release Criteria](../13-testing/10-release-criteria.md)
- [Volume 11 Ch. 07 — Security Acceptance Criteria](../11-security/07-acceptance-criteria.md)
- [Volume 14 Ch. 12 — Runbooks](../14-operations/12-runbooks.md)
- [Volume 16 Ch. 08 — SaaS Acceptance Criteria](../16-saas-multi-tenancy/08-saas-acceptance-criteria.md)
