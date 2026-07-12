# Chapter 07: Acceptance Criteria

**Document ID:** SCP-SEC-001-07  
**Version:** 1.0.0  
**Status:** 📝 Draft  

---

Volume 11 is **complete for Phase 1 Nigeria launch** when all criteria below pass.

## 1. Tenant Isolation

- [ ] Isolation test suite: **0** cross-tenant accesses across API, DB (RLS direct query), cache, search, queue, files
- [ ] Suite runs on every PR; covers 100% of tenant-scoped models

## 2. OWASP ASVS 5.0 Level 2

- [ ] Every applicable L1+L2 requirement mapped to control, test, or documented risk acceptance
- [ ] 100% of L1 requirements verified before Nigeria GA
- [ ] ≥ 95% L2 verified before Phase 2 launch

## 3. PCI DSS SAQ A

- [ ] Redirect/hosted checkout confirmed (ADR-004)
- [ ] SAQ A r1 completed and AoC signed
- [ ] Quarterly ASV scans passing; zero unresolved critical findings

## 4. Nigeria NDPA / GAID (NFR-083) — Launch Blockers

- [ ] **NDPC registration** complete (DCPMI tier confirmed by legal)
- [ ] **NDPC-certified DPO** appointed and documented in RoPA
- [ ] Privacy Policy, Terms, and DPA annex published
- [ ] Subprocessor list published with transfer mechanisms
- [ ] RoPA current; includes biannual DPO compliance report process
- [ ] Breach runbook tested; simulated NDPC notification within 72h workflow
- [ ] Data export and deletion demonstrated end-to-end
- [ ] Cross-border transfer register complete for all Phase 1 subprocessors
- [ ] Primary production infrastructure in Nigeria/West Africa per ADR-011

## 5. Kenya DPA (NFR-084) — Kenya Launch Gate

- [ ] ODPC registration (controller + processor) before Kenya GA
- [ ] Kenya-region data placement for KE merchants
- [ ] Same export/deletion/breach capabilities validated for KE context

## 6. Authentication & Authorization

- [ ] MFA enforced for platform admins (Phase 1)
- [ ] MFA enforced for merchant owners (Phase 2 or earlier if feasible)
- [ ] Credential-stuffing simulation triggers lockout/throttle
- [ ] Authz matrix tests cover every route

## 7. Crypto & Secrets

- [ ] TLS 1.3 verified externally (SSL Labs A+)
- [ ] Zero secrets in repository history (gitleaks)
- [ ] Key rotation drill executed once
- [ ] PII columns verified encrypted at rest

## 8. Edge Security

- [ ] WAF blocking mode; < 0.1% false positive on production sample
- [ ] Rate limits return 429 with `Retry-After`
- [ ] Turnstile active on signup, login failures, checkout

## 9. Audit & Logging

- [ ] All mandatory audit events present with tenant context
- [ ] Alert fires within 5 minutes on injected authz-anomaly test
- [ ] Log PII scrubbing verified

## 10. Supply Chain

- [ ] CI blocks on critical/high vulnerabilities
- [ ] SBOM generated per release
- [ ] Remediation SLAs met over rolling quarter

## 11. Incident Response

- [ ] On-call rotation live
- [ ] SEV1 tabletop: mitigation decision ≤ 30 minutes
- [ ] Post-incident template in use

## 12. Exceptional Conditions (OWASP A10:2025)

- [ ] Missing tenant context → request rejected (fail-closed)
- [ ] Payment callback exceptions never mark orders paid
- [ ] No stack traces reach clients

---

**Sign-off roles:** Lead Architect, DPO, Legal (Nigeria), Security reviewer (TBD).
