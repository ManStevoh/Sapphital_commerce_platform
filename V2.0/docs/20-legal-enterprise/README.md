# Volume 20: Legal & Enterprise Readiness

**Document ID:** SCP-LEG-001  
**Version:** 1.0.0  
**Status:** ✅ Active  
**Depends On:** Volume 1 (Vision), Volume 11 (Security), Volume 16 (SaaS Multi-Tenancy), ADR-004, ADR-011  
**Owner:** Sapphital Learning Company  

---

## Purpose

This volume defines the **legal, regulatory, and enterprise commercial framework** for SCP — translating Nigeria NDPA and GAID 2025 obligations into operational programs, contract templates, expansion playbooks, and enterprise readiness gates. Volume 11 covers technical security controls; this volume covers **legal artifacts, governance, contracts, and certification roadmaps**.

## Scope

- Legal compliance operating model (Nigeria-first)
- Public-facing policy and contract templates (Terms, Privacy, DPA)
- NDPA + GAID 2025 compliance program
- Kenya and Ghana expansion legal requirements
- GDPR readiness for EU enterprise customers
- SOC 2 and ISO 27001 certification roadmap
- Enterprise SLA and master service agreements
- Vendor and subprocessor agreement framework
- Legal acceptance criteria for launch and expansion gates
- Regulatory change management

## Out of Scope

- Detailed technical security implementation (Volume 11)
- Infrastructure runbooks (Volume 10, Volume 14)
- Final executed legal text — counsel of record signs all public policies and enterprise MSAs
- Tax, customs, and product-specific regulatory licensing (Volume 5, Volume 8)

## Audience

| Role | Primary Chapters |
|------|------------------|
| General Counsel / External counsel | 01, 02, 07, 08 |
| Data Protection Officer | 01, 03, 09, 10 |
| Enterprise Sales / Account Management | 07, 05 |
| Engineering leadership | 03, 05, 09 |
| Compliance / Security | 03, 06, 09 |
| Product / Platform | 02, 04, 10 |

## Chapters

| # | Chapter | Status |
|---|---------|--------|
| 01 | [Legal Compliance Overview](./01-legal-compliance-overview.md) | ✅ Active |
| 02 | [Terms, Privacy & DPA Templates](./02-terms-privacy-dpa-templates.md) | ✅ Active |
| 03 | [NDPA GAID Compliance Program](./03-ndpa-gaid-compliance-program.md) | ✅ Active |
| 04 | [Kenya & Ghana Expansion Legal](./04-kenya-ghana-expansion-legal.md) | ✅ Active |
| 05 | [GDPR Enterprise Readiness](./05-gdpr-enterprise-readiness.md) | ✅ Active |
| 06 | [SOC 2 & ISO Roadmap](./06-soc2-iso-roadmap.md) | ✅ Active |
| 07 | [Enterprise SLA & Contracts](./07-enterprise-sla-contracts.md) | ✅ Active |
| 08 | [Vendor & Processor Agreements](./08-vendor-processor-agreements.md) | ✅ Active |
| 09 | [Legal Acceptance Criteria](./09-legal-acceptance-criteria.md) | ✅ Active |
| 10 | [Regulatory Change Management](./10-regulatory-change-management.md) | ✅ Active |

## Standards Baseline (July 2026)

| Standard / Law | Version | SCP Target | Primary Chapter |
|----------------|---------|------------|-----------------|
| Nigeria NDPA | 2023 | Full compliance at Nigeria GA | 03 |
| Nigeria GAID | 2025 (effective 19 Sep 2025) | DCPMI tier compliance | 03 |
| Kenya DPA | 2019 + ODPC guidance | Full compliance at Kenya GA | 04 |
| Ghana DPA | Act 843 (2012) | Full compliance at Ghana GA | 04 |
| GDPR | Regulation 2016/679 | Readiness Phase 3; EU enterprise tier | 05 |
| SOC 2 Type II | AICPA Trust Services Criteria | Type II by H5 2028 | 06 |
| ISO/IEC 27001 | 2022 | Aligned controls; cert optional H6 | 06 |

## Traceability

| Requirement | Volume 20 Coverage |
|-------------|-------------------|
| NFR-072 | GDPR readiness — Chapter 05 |
| NFR-077 | Data export/portability — Chapters 02, 03, 05 |
| NFR-078 | Nigeria NDPA launch gate — Chapters 03, 09 |
| NFR-083 | NDPA primary market — Chapters 01, 03, 09 |
| NFR-084 | Kenya DPA — Chapters 04, 09 |
| NFR-085 | Pan-Africa privacy framework — Chapters 01, 04, 10 |
| PRD-017, PRD-018 | Enterprise tier — Chapters 05, 07 |

## Related Volumes

- [Volume 11 — Security & Compliance](../11-security/README.md)
- [Volume 16 — SaaS Multi-Tenancy](../16-saas-multi-tenancy/README.md)
- [Volume 15 — Future Roadmap](../15-future-roadmap/README.md)
- [ADR-011 — Data Residency Africa](../00-meta/adr/011-data-residency-africa.md)

## Legal Disclaimer

This volume is an **internal engineering and operations specification**. It does not constitute legal advice. All public-facing policies, regulatory filings, and executed contracts require review and approval by qualified legal counsel licensed in the relevant jurisdiction.
