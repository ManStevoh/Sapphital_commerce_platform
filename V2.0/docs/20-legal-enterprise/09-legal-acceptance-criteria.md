# Chapter 09: Legal Acceptance Criteria

**Document ID:** SCP-LEG-001-09  
**Version:** 1.0.0  
**Status:** ✅ Active  
**Traceability:** NFR-078, NFR-083, NFR-084, NFR-072, Volume 11 Ch. 07, Volume 13 Ch. 10  

---

## 1. Purpose

Define **objective legal and enterprise readiness gates** — checklists that must pass before Nigeria GA, Kenya/Ghana launch, GDPR tier activation, and enterprise customer onboarding. This chapter is the legal counterpart to Volume 11 Security Acceptance Criteria.

## 2. Scope

- Phase 1 Nigeria legal launch blockers
- Kenya and Ghana expansion gates
- GDPR enterprise tier gate
- Enterprise contract readiness
- Sign-off roles

## 3. Out of Scope

- Technical security test execution (Volume 11, Volume 13)
- SOC 2 Type II (Chapter 06 — not a Phase 1 blocker)

---

## 4. Nigeria GA — Legal Launch Blockers (NFR-083)

Volume 20 is **complete for Phase 1 Nigeria launch** when all criteria below pass.

### 4.1 Registration & Governance

- [ ] NDPC DCPMI registration certificate obtained and registration number recorded
- [ ] DCPMI tier classification documented with legal sign-off
- [ ] NDPC-certified DPO appointed; contact published in Privacy Policy
- [ ] Governance RACI published (Chapter 01)
- [ ] Compliance Program Manager assigned

### 4.2 Public Legal Documents

- [ ] Terms of Service published at `/legal/terms` with version and effective date
- [ ] Privacy Policy published with Nigeria NDPA section and NDPC registration number
- [ ] DPA annex published at `/legal/dpa`
- [ ] Cookie Policy published; banner functional on Nigeria locale
- [ ] Subprocessor list published; matches internal register (Chapter 08)
- [ ] Acceptable Use Policy published

### 4.3 Consent & Records

- [ ] Signup logs Terms + Privacy version, timestamp, IP
- [ ] Marketing consent separate opt-in with version log
- [ ] Cookie consent stores granular preferences with version ID
- [ ] Re-consent flow tested for material policy change

### 4.4 Operational Privacy

- [ ] RoPA v1.0 complete for all Phase 1 processing activities
- [ ] Data retention schedule published internally and referenced in Privacy Policy
- [ ] DSR export completes within 48 hours for platform account (target 14 days statutory)
- [ ] Account deletion flow demonstrated with deletion certification
- [ ] Processor-path DSR (merchant customer) workflow documented and tested
- [ ] Cross-border transfer register complete with TIAs for US subprocessors

### 4.5 Breach & Incident

- [ ] NDPC breach notification template approved by GC
- [ ] Data subject breach letter template approved
- [ ] Merchant controller notification template approved
- [ ] Breach tabletop completed: simulated 72h NDPC notification
- [ ] Subprocessor breach 24h escalation path tested

### 4.6 Vendor Legal

- [ ] Tier 1 subprocessors: executed DPAs on file (Cloudflare, host, Paystack, Flutterwave)
- [ ] SCCs executed for US subprocessors
- [ ] PSP attestations of compliance collected

### 4.7 Training

- [ ] 100% launch cohort staff completed NDPA privacy training
- [ ] Support team trained on DSR routing

---

## 5. Kenya Launch Gate (NFR-084)

- [ ] ODPC data controller registration certificate
- [ ] ODPC data processor registration certificate
- [ ] Privacy Policy Kenya overlay with ODPC registration number
- [ ] Kenya RoPA annex or section complete
- [ ] Breach runbook includes ODPC 72h notification path
- [ ] Kenya-region data placement verified for KE merchant (ADR-011)
- [ ] M-Pesa / Kenya PSP entries in subprocessor register
- [ ] DSR export/deletion validated for KE test tenant

---

## 6. Ghana Launch Gate

- [ ] DPC registration certificate (controller + processor if required)
- [ ] Privacy Policy Ghana overlay with DPC registration number
- [ ] Cross-border transfer mechanism counsel-approved for Ghana → US subprocessors
- [ ] Ghana payment providers in subprocessor register
- [ ] DSR validated for GH test tenant

---

## 7. GDPR Enterprise Tier Gate (NFR-072)

- [ ] Privacy Policy EU section published
- [ ] EU representative appointed and contact published (if Art. 27 applies)
- [ ] SCCs Module 3 chain complete for EU tenant data path
- [ ] TIA on file for each non-adequate transfer affecting EU tenants
- [ ] `gdpr_tier` tenant flag enables GDPR consent UI
- [ ] EU region provisioning tested (if contract requires residency)
- [ ] Data export schema includes Art. 15 categories
- [ ] Deletion propagates within documented backup window
- [ ] Breach tabletop includes EU supervisory authority path
- [ ] Enterprise MSA GDPR exhibit approved by GC

---

## 8. Enterprise Customer Onboarding Gate

Before first production data for contracted enterprise customer:

- [ ] Executed MSA + Order Form + DPA + SLA exhibits
- [ ] Security exhibit aligned with Volume 11 current controls
- [ ] Subprocessor list acknowledged or 30-day objection window elapsed
- [ ] Named customer security contact registered
- [ ] Incident notification email/phone routing configured
- [ ] SSO configured and tested (if ordered)
- [ ] Dedicated cell provisioned (if ordered)
- [ ] CSM assigned and onboarding checklist complete
- [ ] NDPC registration proof shared in diligence pack

---

## 9. Ongoing Legal Health (Post-Launch)

| Check | Frequency | Owner |
|-------|-----------|-------|
| RoPA accuracy review | Quarterly | DPO |
| Subprocessor list sync | Monthly | Compliance PM |
| Policy version audit | Semi-annual | GC |
| Vendor DPA renewal | Per contract | GC |
| DPO biannual report | Every 6 months | DPO |
| NDPC CAR (if UHL/EHL) | Annual | DPCO + DPO |
| Enterprise SLA report | Monthly | Operations |
| Regulatory change scan | Monthly | Compliance PM (Chapter 10) |

---

## 10. Sign-Off Matrix

| Gate | Required Signatories |
|------|---------------------|
| Nigeria GA legal | General Counsel (Nigeria), DPO, Lead Architect |
| Kenya launch | + ODPC registration confirmed by GC |
| Ghana launch | + DPC registration confirmed by GC |
| GDPR tier | + GC EU privacy counsel |
| Enterprise MSA | GC + Executive sponsor (if non-standard terms) |

Sign-off recorded in release ticket with date and document version references.

---

## 11. Failure Handling

| Condition | Action |
|-----------|--------|
| Any Nigeria blocker incomplete | **No public Nigeria GA** — Volume 13 release criteria fails |
| Subprocessor DPA missing for Tier 1 | Disable integration in production |
| Privacy Policy stale vs processing | Freeze new processing features until updated |
| Missed NDPC breach deadline in drill | Remediate runbook; re-drill within 30 days |

---

## 12. Traceability to Release Criteria

Volume 13 Ch. 10 (Release Criteria) must link:

- `LEGAL-NG-001` → Section 4 (Nigeria blockers)
- `LEGAL-KE-001` → Section 5
- `LEGAL-GH-001` → Section 6
- `LEGAL-GDPR-001` → Section 7
- `LEGAL-ENT-001` → Section 8

---

## 13. Acceptance Criteria (Meta)

This chapter is considered **active and complete** when:

1. Checklists integrated into Volume 13 release criteria.
2. Nigeria GA drill executed with ≥ 95% blockers checked in staging rehearsal.
3. Sign-off matrix communicated to engineering and release management.

---

## 14. Sources

- Volume 11 Ch. 07 — Security Acceptance Criteria
- Volume 13 Ch. 10 — Release Criteria
- Chapter 03 — NDPA GAID Compliance Program
- NFR-078, NFR-083, NFR-084, NFR-072
