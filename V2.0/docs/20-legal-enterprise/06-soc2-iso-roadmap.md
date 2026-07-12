# Chapter 06: SOC 2 & ISO Roadmap

**Document ID:** SCP-LEG-001-06  
**Version:** 1.0.0  
**Status:** ✅ Active  
**Traceability:** NFR-029, PRD-017, PRD-018, Volume 11  

---

## 1. Purpose

Define the **certification and attestation roadmap** for SOC 2 Type II and ISO/IEC 27001 — providing enterprise customers (especially Nigerian banks, telcos, and multinationals) with independent assurance beyond self-attested NDPA compliance.

## 2. Scope

- Trust Services Criteria mapping
- ISO 27001:2022 control alignment
- Timeline and phases
- Evidence collection program
- Bridge letters and customer communication pre-certification

## 3. Out of Scope

- PCI DSS AoC (Volume 11 — separate SAQ A program)
- NDPC compliance audit (Chapter 03 — DPCO-led)
- FedRAMP / HIPAA (not planned)

---

## 4. Why SOC 2 + ISO for SCP

| Driver | Detail |
|--------|--------|
| Enterprise procurement | Nigerian corporates request SOC 2 or ISO 27001 |
| International expansion | EU/US enterprise due diligence |
| Insurance | Cyber insurance premium reduction |
| Internal discipline | Formal control operating effectiveness |

**Nigeria NDPA does not require SOC 2** — this is a **commercial and risk program**, not a Phase 1 launch blocker.

---

## 5. Roadmap Timeline

| Phase | Horizon | Milestone | Customer-Facing |
|-------|---------|-----------|-----------------|
| **Foundation** | H1–H2 2027 | Control design; evidence tooling; policies | Security whitepaper |
| **SOC 2 Type I** | H3 2027 | Point-in-time design audit | Type I report (NDA) |
| **SOC 2 Type II** | H5 2028 | 6–12 month operating period | Type II report (NDA) |
| **ISO 27001 align** | H4–H5 2028 | ISMS documentation; internal audit | Statement of alignment |
| **ISO 27001 cert** | H6 2029 | Stage 1 + Stage 2 external audit | Certificate (optional) |

Bridge letter from auditor acceptable for enterprise deals closed between Type I and Type II.

---

## 6. SOC 2 Trust Services Criteria

SCP scope: **Security** (mandatory) + **Availability** + **Confidentiality**.

| TSC Category | SCP Scope | Primary Evidence |
|--------------|-----------|------------------|
| **Security (CC)** | Full platform | Volume 11 controls, pen test, access reviews |
| **Availability (A)** | Production SLA | Uptime reports, DR drills, incident metrics |
| **Confidentiality (C)** | Customer PII | Encryption, access control, NDAs |
| Processing Integrity | Exclude Phase 1 | Order accuracy covered by commerce QA |
| Privacy | Overlap with NDPA | RoPA, DSR — optional TSC add-on later |

### 6.1 SOC 2 System Description (Outline)

| Section | Content |
|---------|---------|
| Company overview | Sapphital Learning Company, SCP product |
| System boundaries | Multi-tenant SaaS, Nigeria primary DC, Cloudflare edge |
| Principal service commitments | SLA, privacy policy, security whitepaper |
| System components | App tier, PostgreSQL, Redis, queue, object storage |
| Subservice organizations | Cloudflare, AWS/Hetzner, Paystack — carve-out or inclusive |
| Control environment | Governance, risk, monitoring |

---

## 7. Control Mapping — SOC 2 ↔ SCP

| CC Ref | Control Theme | SCP Implementation |
|--------|---------------|-------------------|
| CC1.1 | Integrity & ethics | Code of conduct, security policy |
| CC2.1 | Board oversight | Quarterly security review |
| CC3.1 | Risk assessment | STRIDE (Vol 11 Ch. 03), DPIA |
| CC5.1 | Control activities | ASVS L2, CI gates |
| CC6.1 | Logical access | MFA admins, RBAC, RLS |
| CC6.6 | Boundary protection | WAF, network segmentation |
| CC7.1 | System monitoring | Observability (NFR-062–070) |
| CC7.2 | Anomaly detection | SIEM alerts, authz anomalies |
| CC7.3 | Incident response | Vol 11 Ch. 06 |
| CC8.1 | Change management | PR review, CI, staged deploy |
| CC9.1 | Vendor management | Chapter 08 subprocessor program |
| A1.1 | Availability | 99.9% SLA, multi-AZ |
| C1.1 | Confidentiality | Encryption at rest/transit |

Full mapping spreadsheet: `docs/20-legal-enterprise/appendices/soc2-control-mapping.csv` (maintained by Compliance PM).

---

## 8. ISO/IEC 27001:2022 Alignment

### 8.1 ISMS Scope Statement (Draft)

> The Information Security Management System for the SAPPHITAL Commerce Platform covering multi-tenant SaaS production environment, supporting infrastructure, and corporate systems that process merchant and customer data for Nigeria and expansion markets.

### 8.2 Annex A Control Themes (Selected)

| ISO Control | SCP Alignment |
|-------------|---------------|
| A.5 Organizational | Policies, roles, supplier relationships |
| A.6 People | Background checks, training, NDPA awareness |
| A.7 Physical | Data center provider SOC reports |
| A.8 Technological | Crypto, access, logging, secure SDLC |
| A.5.23 Cloud services | Subprocessor DPAs, shared responsibility matrix |

ISO certification is **optional** — alignment sufficient for most African enterprise RFPs once SOC 2 Type II available.

---

## 9. Evidence Program

| Evidence Type | Collection | Retention |
|---------------|------------|-----------|
| Access reviews | Quarterly IAM export + manager sign-off | 3 years |
| Change tickets | GitHub PR + deploy log | 3 years |
| Pen test reports | Annual third-party | 7 years |
| Vulnerability scans | Quarterly ASV + continuous CI | 1 year |
| Incident records | Postmortem + timeline | 7 years |
| Backup restore tests | Semi-annual | 3 years |
| Vendor SOC reports | Annual collection from subprocessors | 3 years |
| Training records | LMS completion | 3 years |

Store in GRC folder: `compliance/evidence/YYYY/QN/`.

---

## 10. Auditor Selection

| Criterion | Requirement |
|-----------|-------------|
| Accreditation | AICPA (SOC 2), UKAS/ANAB (ISO) |
| Africa experience | Preferred — understands NDPA context |
| Subservice org method | Confirm carve-out vs inclusive for Cloudflare |
| Cost envelope | Budget ₦15M–₦40M for Type I + Type II cycle |

Engage auditor at **H2 2026** for readiness assessment.

---

## 11. Subservice Organization Strategy

| Vendor | Likely Method | SCP Action |
|--------|---------------|------------|
| Cloudflare | Carve-out (their SOC 2) | Obtain bridge letter annually |
| Cloud host (Hetzner/AWS) | Carve-out or inclusive | Confirm with auditor |
| Paystack | Carve-out | PSP AoC on file |
| OpenAI | Carve-out | Enterprise DPA + SOC 2 if available |

---

## 12. Customer Communication

| Stage | Sales Artifact |
|-------|----------------|
| Pre-Type I | Security whitepaper + pen test summary + NDPC registration |
| Type I issued | NDA-bound report share |
| Type II in progress | Bridge letter + expected completion date |
| Type II issued | Standard enterprise diligence pack |

Never claim "SOC 2 certified" before Type II completion — use **"SOC 2 Type II in progress"** with bridge letter.

---

## 13. Acceptance Criteria

### Foundation (H2 2027)

1. SOC 2 system description v1.0 approved by management.
2. Control mapping 100% drafted for Security + Availability + Confidentiality.
3. Evidence repository operational with Q1 sample pack.
4. Quarterly access review completed and signed.

### Type I (H3 2027)

5. Unqualified Type I opinion received.
6. No critical findings open > 90 days.

### Type II (H5 2028)

7. Unqualified Type II opinion after 6+ month observation window.
8. Report shared with 3 pilot enterprise customers under NDA.

---

## 14. Sources

- AICPA Trust Services Criteria: https://www.aicpa.org/resources/article/trust-services-criteria
- ISO/IEC 27001:2022: https://www.iso.org/standard/27001
- Volume 11 — Security architecture and testing
- OWASP ASVS 5.0 — technical control baseline
