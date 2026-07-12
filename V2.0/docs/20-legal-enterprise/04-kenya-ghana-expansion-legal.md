# Chapter 04: Kenya & Ghana Expansion Legal

**Document ID:** SCP-LEG-001-04  
**Version:** 1.0.0  
**Status:** ✅ Active  
**Traceability:** NFR-084, NFR-085, ADR-011  

---

## 1. Purpose

Define **legal and regulatory requirements** for SCP expansion into **Kenya** (Phase 1b primary) and **Ghana** (Phase 1b parallel), extending the Nigeria privacy core without duplicating infrastructure or policy silos.

## 2. Scope

- Kenya Data Protection Act 2019 and ODPC requirements
- Ghana Data Protection Act 843 (2012) and DPC requirements
- Registration, DPO/Representative, breach, transfers
- Data residency expectations
- Country overlay process for public policies

## 3. Out of Scope

- Francophone West Africa (Volume 15 Ch. 05)
- South Africa POPIA (Phase 2 — register in Country Compliance Register only)

---

## 4. Expansion Principles

| Principle | Implementation |
|-----------|----------------|
| **Nigeria program first** | Kenya/Ghana gates do not reduce Nigeria obligations |
| **One privacy core** | Same DSR APIs, consent engine, breach runbook |
| **Jurisdiction overlays** | Policy sections + regulator contacts per country |
| **Regional residency** | Kenya merchants → East Africa cell (ADR-011) |
| **No launch without registration** | ODPC/DPC filing before public marketing |

---

## 5. Kenya — Data Protection Act 2019

### 5.1 Regulatory Overview

| Field | Value |
|-------|-------|
| Primary law | Data Protection Act, 2019 (No. 24 of 2019) |
| Regulator | **Office of the Data Protection Commissioner (ODPC)** |
| SCP roles | Controller (merchant accounts) + Processor (end-customer data) |
| Portal | https://www.odpc.go.ke/ |

### 5.2 Registration Requirements

| Registration Type | When | Evidence |
|-------------------|------|----------|
| Data controller registration | Before Kenya GA | ODPC certificate |
| Data processor registration | Before Kenya GA | ODPC certificate |
| Renewal | Per ODPC schedule | Compliance PM tracks |

Registration application includes: organization details, processing description, security measures summary, DPO contact (may be same NDPC-certified DPO with Kenya registration if ODPC accepts cross-border DPO — confirm with counsel).

### 5.3 Kenya-Specific Obligations

| Obligation | Requirement | SCP Action |
|------------|-------------|------------|
| Lawful processing | Consent, contract, legal obligation, vital interests, public task, legitimate interest | Same lawful basis matrix as Nigeria; Kenya section in Privacy Policy |
| Data subject rights | Access, rectification, erasure, objection, portability | Existing DSR tooling (NFR-077) |
| Breach notification | **72 hours to ODPC** | Extend breach runbook with ODPC form fields |
| Cross-border transfer | §48–50 — adequacy or safeguards | Transfer register; SCCs for US subprocessors |
| DPIA | High-risk processing | Reuse Nigeria DPIA templates; Kenya regulator reference |
| Data localization | No general mandatory localization in DPA | NFR-071 East Africa deployment for KE merchants |

### 5.4 Kenya Data Residency

Per ADR-011 and NFR-071:

- **Kenya-region production** for merchants with `country_code = KE` at signup
- Backups remain in East Africa cluster
- Cross-border transfers documented even within Africa if subprocessors process abroad

### 5.5 Kenya Launch Legal Checklist

| # | Item | Owner |
|---|------|-------|
| 1 | ODPC controller registration | GC |
| 2 | ODPC processor registration | GC |
| 3 | Privacy Policy Kenya overlay published | GC + DPO |
| 4 | M-Pesa / local PSP subprocessor entries in register | DPO |
| 5 | Kenya RoPA annex (or RoPA section) | DPO |
| 6 | Breach runbook ODPC path tested | Security + DPO |
| 7 | KE merchant signup with Kenya locale + residency routing | Engineering |
| 8 | DSR validated for KE test merchant | QA |

---

## 6. Ghana — Data Protection Act 843 (2012)

### 6.1 Regulatory Overview

| Field | Value |
|-------|-------|
| Primary law | Data Protection Act, 2012 (Act 843) |
| Regulator | **Data Protection Commission (DPC)** |
| Portal | https://dataprotection.org.gh/ |

Ghana's framework predates NDPA but aligns on core principles: lawful processing, purpose limitation, data quality, security, openness, individual participation, accountability.

### 6.2 Registration Requirements

| Step | Detail |
|------|--------|
| Register as data controller | Before Ghana GA |
| Register as data processor (if applicable) | Before Ghana GA |
| Pay registration fee | Per DPC schedule |
| Display registration certificate | Privacy Policy Ghana section |

### 6.3 Ghana-Specific Considerations

| Topic | SCP Approach |
|-------|--------------|
| Consent | Explicit consent for marketing; documented |
| Transfer abroad | DPC notification/approval historically required for transfers — **counsel confirms current DPC guidance** before routing Ghana merchant data to US subprocessors |
| Data Protection Officer | Appoint contact; may align with platform DPO |
| Breach | Notify DPC without undue delay; align to 72h internal target |
| Electronic transactions | Align Terms with Ghana E-Transactions Act where applicable |

### 6.4 Ghana Launch Legal Checklist

| # | Item | Owner |
|---|------|-------|
| 1 | DPC registration (controller + processor) | GC |
| 2 | Privacy Policy Ghana overlay | GC + DPO |
| 3 | Transfer mechanism validated for Ghana → US subprocessors | GC + DPO |
| 4 | Mobile money PSP (Hubtel, etc.) in subprocessor list | DPO |
| 5 | Ghana tax/display compliance cross-ref Volume 5 | Product |
| 6 | Francophone not required; English policy sufficient Phase 1b | Product |

---

## 7. Country Compliance Register

Maintain internal register (spreadsheet or GRC tool):

| Country | Law | Regulator | Reg # | Reg Date | DPO Contact | Residency | Launch Status |
|---------|-----|-----------|-------|----------|-------------|-----------|---------------|
| Nigeria | NDPA + GAID | NDPC | | | | Lagos | Phase 1 |
| Kenya | DPA 2019 | ODPC | | | | East Africa | Phase 1b |
| Ghana | Act 843 | DPC | | | | West Africa | Phase 1b |

Update within **10 business days** of registration or material regulatory change.

---

## 8. Policy Overlay Template

For each new African market, add to Privacy Policy:

```markdown
## [Country] Addendum

**Regulator:** [Name, URL]
**Registration:** [Number]
**Local contact:** [Email/address if required]
**Specific rights:** [Any local variations]
**Complaints:** [Local regulator complaint procedure]
```

Terms of Service governing law remains Nigeria for standard SaaS unless enterprise MSA specifies otherwise.

---

## 9. Payment & Sector Overlays

| Market | PSP | Legal Note |
|--------|-----|------------|
| Kenya | M-Pesa (Safaricom), Paystack KE | PSP DPAs; ODPC registration of PSPs not SCP responsibility but list as subprocessors |
| Ghana | Paystack GH, Hubtel | CBN-equivalent Bank of Ghana awareness for merchant KYC |

---

## 10. Risks

| Risk | Mitigation |
|------|------------|
| Assuming Nigeria registration covers Kenya/Ghana | Separate filings mandatory |
| US subprocessor transfers blocked in Ghana | Pre-launch TIA + counsel opinion |
| Inconsistent DSR SLAs across markets | Single SLA in privacy core (14-day target) |
| Marketing before registration | Launch gate in Volume 13 release criteria |

---

## 11. Acceptance Criteria

### Kenya Gate (NFR-084)

1. ODPC controller and processor registration certificates obtained.
2. Kenya Privacy Policy overlay published with ODPC registration number.
3. Kenya-region data placement verified for KE test merchant.
4. Breach tabletop includes ODPC notification path.
5. DSR export/deletion validated for KE context.

### Ghana Gate

1. DPC registration complete.
2. Ghana Privacy Policy overlay published.
3. Cross-border transfer mechanism counsel-approved.
4. Subprocessor list includes Ghana payment providers.

---

## 12. Sources

- Kenya Data Protection Act 2019: https://www.odpc.go.ke/data-protection-act/
- ODPC registration guidance: https://www.odpc.go.ke/
- Ghana Data Protection Commission: https://dataprotection.org.gh/
- Volume 11 Ch. 02 — Kenya section
- ADR-011 — Data residency
