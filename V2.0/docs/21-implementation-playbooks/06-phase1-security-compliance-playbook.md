# Chapter 06: Phase 1 — Security & Compliance Playbook

**Document ID:** SCP-IMP-021-06  
**Version:** 1.0.0  
**Status:** ✅ Active  
**Traceability:** NFR-029, NFR-044, NFR-078, NFR-083, Volume 11, ADR-004 – ADR-011  

---

## Purpose

Parallel build track for **security architecture and Nigeria regulatory compliance** — executed from Week 1 alongside foundation work, gating Nigeria GA. Security is not a final sprint; it is continuous implementation work with explicit checkpoints.

## Scope

- OWASP ASVS 5.0 Level 2 control implementation
- Nigeria NDPA / GAID compliance program
- PCI DSS SAQ A evidence collection
- Threat model mitigations (STRIDE)
- Security testing integration in CI
- Incident response readiness

## Out of Scope

- Kenya ODPC registration (Kenya launch pack)
- Penetration test vendor selection (execute 4 weeks before GA)
- Legal drafting of Terms and Privacy Policy (legal counsel owns text)

## Prerequisites

- [ ] Volume 11 read by engineering and legal stakeholders
- [ ] NDPC registration process initiated Week 1
- [ ] Security lead assigned (may be lead architect in Phase 1)

---

## §1 Security Program Setup (Week 1)

### 1.1 Governance

**Checklist:**

- [ ] Security workstream owner assigned with weekly checkpoint
- [ ] OWASP ASVS 5.0 L2 spreadsheet created: control → implementation → test → status
- [ ] Threat model reviewed ([Volume 11 Ch. 03](../11-security/03-threat-model.md)); mitigations assigned to sprints
- [ ] Security review required on PRs touching: auth, payments, tenant isolation, encryption
- [ ] Risk acceptance process documented for deferred controls (requires ADR)

### 1.2 Secure Development Baseline

Per [Volume 11 Ch. 04](../11-security/04-security-architecture.md):

- [ ] Semgrep ruleset: SQL injection, XSS, hardcoded secrets, insecure crypto
- [ ] gitleaks pre-commit hook + CI history scan
- [ ] Dependency audit: block merge on critical/high CVE
- [ ] SBOM generation per release artifact
- [ ] Security training: OWASP Top 10 2025 for all engineers (Week 1 onboarding)

---

## §2 Authentication & Authorization Hardening (Weeks 3–8)

Cross-reference [Chapter 02 §3](./02-phase1-foundation-playbook.md):

| Control | Implementation | Test |
|---------|----------------|------|
| MFA platform admins | TOTP required | Login without MFA fails |
| Password policy | ≥ 12 chars, breach check | Unit test |
| Session security | Secure, HttpOnly, SameSite=Lax | Cookie inspection |
| Rate limiting | 5 failures → 15 min lockout | Automated brute force test |
| RBAC enforcement | Middleware on every route | Authz matrix test suite |
| API token scoping | Explicit permissions per token | Cross-permission denial test |
| Impersonation audit | ADR-010 full implementation | Audit log verification |

**Checklist:**

- [ ] Authz matrix test covers 100% of admin and API routes
- [ ] Credential stuffing simulation triggers lockout and alert
- [ ] Session fixation attack test fails
- [ ] CSRF protection on all state-changing admin forms

---

## §3 Tenant Isolation & Data Protection (Weeks 2–12)

Per NFR-040 and [Volume 13 Ch. 04](../13-testing/04-tenant-isolation-test-suite.md):

**Checklist:**

- [ ] RLS policies on 100% of tenant-scoped PostgreSQL tables
- [ ] Application-level global scope on all Eloquent models
- [ ] Isolation test suite: 0 cross-tenant reads/writes across API, DB, cache, search, queue, files
- [ ] PII columns encrypted at rest (email, phone, address) — Laravel encrypted cast
- [ ] Data minimization: collect only required checkout fields
- [ ] Log PII scrubbing verified — no email/phone in production logs
- [ ] Backup encryption at rest (PostgreSQL + R2)

---

## §4 Edge Security (Weeks 4–10)

Per [ADR-008](../00-meta/adr/008-edge-security-cloudflare.md):

**Checklist:**

- [ ] Cloudflare WAF in blocking mode (OWASP Core Ruleset)
- [ ] Bot Fight Mode + Turnstile on signup, login, checkout
- [ ] Rate limits: 120 req/min storefront, 60 req/min API per IP
- [ ] DDoS protection via Cloudflare automatic mitigation
- [ ] TLS 1.3 only; SSL Labs grade A+ verified externally
- [ ] HSTS header with `max-age=31536000; includeSubDomains`
- [ ] Geographic restriction option for admin (Nigeria + VPN allowlist for remote team)

---

## §5 Encryption & Secrets (Weeks 2–8)

Per [ADR-007](../00-meta/adr/007-secrets-management.md):

**Checklist:**

- [ ] All secrets in vault (not `.env` in production); rotation procedure documented
- [ ] PSP secret keys encrypted in database
- [ ] Key rotation drill executed once before GA
- [ ] Zero secrets in git history (gitleaks verified)
- [ ] Database connections use TLS (mandatory Phase 2; optional Phase 1 with ADR note)
- [ ] Webhook signing secrets unique per store

---

## §6 Audit Logging (Weeks 5–10)

Per [ADR-009](../00-meta/adr/009-audit-log-immutability.md):

**Mandatory audit events:**

| Event | Context Required |
|-------|------------------|
| `auth.login`, `auth.logout`, `auth.failed` | user_id, ip, user_agent |
| `auth.mfa.enabled`, `auth.mfa.challenge` | user_id |
| `impersonation.start`, `impersonation.end` | admin_id, tenant_id |
| `product.created`, `product.deleted` | tenant_id, product_id, actor |
| `order.status_changed` | tenant_id, order_id, from, to |
| `payment.refunded` | tenant_id, payment_id, amount |
| `settings.payment_keys_updated` | tenant_id, provider |
| `tenant.suspended`, `tenant.deleted` | tenant_id, reason |
| `data.export_requested`, `data.deletion_requested` | tenant_id, subject_id |

**Checklist:**

- [ ] Append-only audit table; application DB user has no UPDATE/DELETE on audit table
- [ ] All events include `tenant_id` where applicable
- [ ] Alert fires within 5 minutes on injected authz-anomaly test
- [ ] Audit log retention: 7 years with anonymized tenant ID after deletion
- [ ] Admin UI: audit log viewer for merchant Owner role (own tenant only)

---

## §7 Nigeria NDPA Compliance Program (Weeks 1–16)

Per [Volume 11 Ch. 02](../11-security/02-africa-regulatory-compliance.md) and NFR-083:

### 7.1 Registration & Governance

**Checklist:**

- [ ] NDPC registration complete (DCPMI tier confirmed by legal counsel)
- [ ] NDPC-certified Data Protection Officer appointed
- [ ] Data Protection Impact Assessment (DPIA) completed for Phase 1 processing activities
- [ ] Record of Processing Activities (RoPA) published internally; updated biannually
- [ ] DPO compliance report process scheduled (biannual)

### 7.2 Legal Documents

- [ ] Privacy Policy published on sapphital.com and merchant template provided
- [ ] Terms of Service published
- [ ] Data Processing Agreement (DPA) annex for merchants
- [ ] Cookie/tracking disclosure (minimal Phase 1; analytics disclosed)
- [ ] Merchant responsible for own customer privacy policy (template + checklist in admin)

### 7.3 Data Subject Rights

- [ ] Export: self-serve JSON/CSV within 48 hours (NFR-083)
- [ ] Deletion: request → export window 30 days → soft delete 90 days → hard delete
- [ ] Consent capture on checkout (marketing opt-in explicit, not pre-checked)
- [ ] Data subject request admin queue for platform admin

### 7.4 Subprocessors & Transfers

- [ ] Subprocessor register published: Cloudflare, Paystack, Flutterwave, Termii, email provider, AI provider
- [ ] Cross-border transfer register with legal basis for each subprocessor
- [ ] Data Processing Agreements signed with all subprocessors
- [ ] Primary production infrastructure in Nigeria/West Africa (ADR-011)

### 7.5 Breach Response

- [ ] Breach runbook documented ([Volume 11 Ch. 06](../11-security/06-incident-response.md))
- [ ] Simulated breach exercise: NDPC notification within 72-hour workflow
- [ ] Breach severity classification matrix
- [ ] Customer notification template prepared

**Gate §7:** Legal sign-off on NDPA checklist; DPO confirms RoPA current.

---

## §8 PCI DSS SAQ A (Weeks 10–16)

Cross-reference [Chapter 05 §7](./05-phase1-payments-nigeria-playbook.md):

**Checklist:**

- [ ] Network diagram showing SCP out of cardholder data environment
- [ ] SAQ A r1 questionnaire completed with evidence attachments
- [ ] Attestation of Compliance signed by authorized officer
- [ ] Quarterly ASV scan scheduled and first scan passing
- [ ] PCI test pack in CI: 0 failures ([Volume 13 Ch. 07](../13-testing/07-security-testing.md))
- [ ] Employee PCI awareness training recorded

---

## §9 Security Testing Schedule (Weeks 8–16)

Per [Volume 11 Ch. 05](../11-security/05-security-testing.md) and [Volume 13 Ch. 07](../13-testing/07-security-testing.md):

| Activity | Frequency | Gate |
|----------|-----------|------|
| Semgrep SAST | Every PR | Blocking |
| gitleaks | Every PR | Blocking |
| Dependency audit | Every PR | Blocking critical/high |
| OWASP ZAP baseline | Weekly staging | 0 high findings at GA |
| Tenant isolation suite | Every PR | Blocking |
| PCI test pack | Every PR + pre-release | Blocking |
| Authz matrix | Every PR | Blocking |
| External penetration test | Once pre-GA | 0 critical open at launch |
| Credential stuffing simulation | Pre-GA | Pass |

---

## §10 Incident Response Readiness (Week 14–16)

Per [Volume 14 Ch. 04](../14-operations/04-incident-management.md):

**Checklist:**

- [ ] On-call rotation defined with primary and secondary
- [ ] PagerDuty/Opsgenie integrated with alerting rules
- [ ] Severity definitions: SEV1 (checkout down), SEV2 (payment webhook failure), SEV3 (degraded)
- [ ] Incident communication templates (internal, merchant, public status page)
- [ ] Post-incident review template and 5-Whys process
- [ ] NDPA breach notification workflow tested in tabletop exercise

---

## §11 Phase 1 Security — Complete Checklist

| # | Domain | Launch Blocker | Status |
|---|--------|----------------|--------|
| 1 | OWASP ASVS L1 — 100% verified | Yes | ☐ |
| 2 | OWASP ASVS L2 — ≥ 95% verified | Yes | ☐ |
| 3 | Tenant isolation 0 failures | Yes | ☐ |
| 4 | NDPC registration + DPO | Yes | ☐ |
| 5 | Privacy Policy + Terms + DPA | Yes | ☐ |
| 6 | Data export/deletion demonstrated | Yes | ☐ |
| 7 | Subprocessor register + DPAs | Yes | ☐ |
| 8 | PCI SAQ A AoC signed | Yes | ☐ |
| 9 | WAF blocking mode active | Yes | ☐ |
| 10 | MFA on platform admins | Yes | ☐ |
| 11 | Audit events complete | Yes | ☐ |
| 12 | Penetration test 0 critical | Yes | ☐ |
| 13 | Breach runbook tested | Yes | ☐ |
| 14 | On-call rotation live | Yes | ☐ |

---

## Dependencies

| Volume | Usage |
|--------|-------|
| [Volume 11](../11-security/README.md) | Full security specification |
| [Volume 13 Ch. 07](../13-testing/07-security-testing.md) | Security test execution |
| [Volume 14](../14-operations/README.md) | Incident management |
| [Volume 10 Ch. 05](../10-infrastructure/05-storage-cdn-cloudflare.md) | Edge security |
| Research Track 20 | International standards and legal |

## Next Chapter

After Phase 1 security gates pass, proceed to [Chapter 07 — Growth Playbook](./07-phase2-growth-playbook.md) or finalize [Chapter 12 — Launch Readiness](./12-launch-readiness-checklist.md) for Nigeria GA.

---

## References

- [Volume 11 Ch. 07 — Acceptance Criteria](../11-security/07-acceptance-criteria.md)
- [Volume 13 Ch. 10 — Release Criteria](../13-testing/10-release-criteria.md)
- [Nigeria NDPA 2023](https://ndpc.gov.ng/)
