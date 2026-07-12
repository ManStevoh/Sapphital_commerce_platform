# Chapter 10: Release Criteria

**Document ID:** SCP-TEST-001-10  
**Version:** 1.0.0  
**Status:** ✅ Active  
**Traceability:** NFR-021, NFR-044, NFR-083, NFR-084, Volume 11 Ch. 07

---

## Purpose

Define **release readiness criteria** for SCP deployments — from weekly patches to Nigeria GA and Kenya corridor launch — ensuring no release ships without verified quality, security, and compliance evidence.

## Scope

- Release types and cadence
- Go/no-go checklist per release type
- Sign-off roles and responsibilities
- Rollback criteria post-release
- Communication requirements
- Compliance evidence package

## Out of Scope

- CI pipeline configuration (Chapter 09)
- Marketing launch communications
- Feature flag strategy (Volume 16 Ch. 06)

---

## 1. Release Types

| Type | Cadence | Example | Gate Level |
|------|---------|---------|------------|
| **Patch** | As needed | Hotfix SEV1 | Abbreviated |
| **Minor** | Bi-weekly | New admin feature | Standard |
| **Major** | Quarterly | Marketplace GA | Full |
| **GA Launch** | Milestone | Nigeria public launch | Maximum |
| **Regional** | Per corridor | Kenya GA | Regional pack |

---

## 2. Standard Release Checklist (Minor/Major)

### 2.1 Engineering

- [ ] All PR merge gates green on release branch
- [ ] 3 consecutive nightly pipelines green
- [ ] Changelog and migration notes published
- [ ] Feature flags default-safe (new features off or beta)
- [ ] Rollback procedure (RB-002) validated in staging
- [ ] Database migrations backward-compatible or compat layer ready

### 2.2 Quality

- [ ] E2E critical paths pass (checkout, admin order, tenant signup)
- [ ] Tenant isolation extended suite: 0 failures
- [ ] Lighthouse mobile ≥ 85 on reference theme
- [ ] Golden-route visual regression passes for homepage, collection, PDP, search, cart, and AI assistant
- [ ] Five-second comprehension test passes for changed/new reference-theme homepage
- [ ] Fixed-layer collision suite passes at 320, 375, and 414 px
- [ ] No P0/P1 open bugs in release scope
- [ ] No quarantined flaky tests in critical paths

### 2.3 Security

- [ ] 0 critical/high CVE in production dependencies
- [ ] Semgrep blocking rules pass
- [ ] OWASP ZAP baseline: 0 high findings on staging
- [ ] gitleaks history scan pass

### 2.4 Operations

- [ ] Staging soak ≥ 24h (major: 48h)
- [ ] On-call briefed with release notes
- [ ] Monitoring dashboards reviewed for baseline
- [ ] Runbook RB-001 assigned engineer

---

## 3. Nigeria GA Launch Checklist (Maximum)

All standard items **plus:**

### 3.1 Compliance (Launch Blockers)

- [ ] NDPC registration complete (Volume 11)
- [ ] NDPC-certified DPO appointed
- [ ] Privacy Policy, Terms, DPA annex published
- [ ] Subprocessor register with transfer mechanisms
- [ ] RoPA current; breach runbook tested (72h NDPC workflow)
- [ ] Data export and deletion demonstrated end-to-end
- [ ] Production infrastructure in Nigeria/West Africa (ADR-011)

### 3.2 Payments

- [ ] Paystack live mode checkout e2e verified
- [ ] Flutterwave live mode verified (if enabled)
- [ ] Webhook reconciliation job tested with missed webhook simulation
- [ ] PCI SAQ A completed; AoC signed (ADR-004)
- [ ] No card data stored on SCP — PCI test pack pass

### 3.3 Performance & Reliability

- [ ] 99.9% SLO sustained on staging load test
- [ ] k6 load: 500 concurrent shoppers, p95 < 500ms
- [ ] DR restore drill within RTO 4h
- [ ] External synthetic monitoring live (Nigeria probe)

### 3.4 Product Readiness

- [ ] Merchant onboarding flow e2e (signup → first product → first sale)
- [ ] Support runbooks and macros ready (Volume 14 Ch. 07)
- [ ] Status page live (Volume 14 Ch. 08)
- [ ] Pricing plans and billing active (Volume 16)
- [ ] Reference storefront demonstrates category, trust, and purchase action above the fold
- [ ] Product Card and PDP responsive states approved against Volume 4 Chapter 13

---

## 4. Kenya Corridor Launch Pack

All standard items **plus:**

- [ ] ODPC registration (controller + processor)
- [ ] Kenya region compute live (`af-ke-nairobi`)
- [ ] M-Pesa checkout e2e in staging
- [ ] KE tenant data residency validated
- [ ] Export/deletion/breach validated for KE context (NFR-084)

---

## 5. Patch / Hotfix Abbreviated Checklist

**Allowed only for SEV1/SEV2 production incidents:**

- [ ] Root cause identified
- [ ] Fix PR reviewed by 2 engineers
- [ ] Targeted test proves fix
- [ ] Tenant isolation smoke pass
- [ ] Lead Architect approval
- [ ] Post-deploy monitoring 30 min

Skip nightly soak; full regression within 48h.

---

## 6. Go/No-Go Meeting

| Attribute | Detail |
|-----------|--------|
| Timing | 24h before production deploy (GA: 48h) |
| Attendees | Lead Architect, on-call lead, QA lead, DPO (GA), Product |
| Input | Checklist completion, open risks |
| Output | GO / NO-GO / GO with conditions |
| Record | Decision logged in release ticket |

**NO-GO triggers:** Any launch blocker unchecked; error budget < 25% remaining; unresolved SEV1.

---

## 7. Post-Release Monitoring (First 72 Hours)

| Metric | Threshold | Action |
|--------|-----------|--------|
| Error rate | > 2× baseline 15 min | Rollback evaluation |
| Checkout success | < 95% 1h | SEV2 incident |
| API p95 | > 2× SLO 30 min | Scale up + investigate |
| Support tickets | > 3× daily avg | Product comms review |
| Isolation anomaly | Any | SEV1 immediate rollback |

---

## 8. Rollback Decision Criteria

Execute RB-002 when:

- SEV1 data integrity or security issue
- Checkout success < 90% for 30 minutes
- Error rate > 5% for 10 minutes
- Failed migration corrupting data

Lead Architect or on-call lead authorizes; DPO notified if PII involved.

---

## 9. Release Evidence Package

Archived per release in `compliance/releases/{version}/`:

| Document | Source |
|----------|--------|
| CI pipeline URLs | GitHub Actions |
| Nightly reports (3) | CI artifacts |
| Isolation suite report | Pest output |
| Lighthouse/k6 summaries | CI artifacts |
| CVE scan | composer/npm audit |
| ZAP report | SARIF |
| Go/no-go memo | Release ticket |
| Changelog | CHANGELOG.md |

Retention: 3 years for GA; 1 year for minor/patch.

---

## 10. Sign-Off Roles

| Role | Standard | GA Launch |
|------|----------|-----------|
| Lead Architect | Required | Required |
| Engineering on-call lead | Required | Required |
| QA / Quality lead | Required | Required |
| DPO (Nigeria) | — | Required |
| Legal (Nigeria) | — | Required |
| Product lead | Major/GA | Required |
| Security reviewer | Major/GA | Required |

---

## 11. Acceptance Criteria

- [ ] Release types: patch, minor, major, GA, regional defined
- [ ] Standard checklist: engineering, quality, security, ops
- [ ] Nigeria GA blockers: NDPC, DPO, residency, PCI SAQ A
- [ ] Kenya pack: ODPC, M-Pesa, KE residency
- [ ] Hotfix abbreviated checklist with 2-reviewer rule
- [ ] Go/no-go meeting 24h before deploy
- [ ] Post-release 72h monitoring thresholds
- [ ] Evidence package contents and retention documented

---

## References

- [Chapter 09 — CI Quality Gates](./09-ci-quality-gates.md)
- [Volume 11 Ch. 07 — Security Acceptance](../11-security/07-acceptance-criteria.md)
- [Volume 10 Ch. 12 — Runbooks](../10-infrastructure/12-runbooks.md)
- [Volume 14 Ch. 10 — Operations Acceptance](../14-operations/10-operations-acceptance-criteria.md)
