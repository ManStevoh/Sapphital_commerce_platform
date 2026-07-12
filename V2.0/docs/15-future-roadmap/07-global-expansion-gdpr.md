# Chapter 07: Global Expansion & GDPR

**Document ID:** SCP-ROAD-001-07  
**Version:** 1.0.0  
**Status:** ✅ Active  
**Traceability:** NFR-072, ADR-011, Volume 11  

---

## Purpose

Plan SCP expansion **beyond Africa** into EU, UK, and other jurisdictions with GDPR-class privacy law, without compromising Nigeria-first operations.

## Scope

- GDPR readiness activation (NFR-072 Phase 3)
- EU/UK data residency options
- Cross-border transfer from Nigeria/Africa
- Payment expansion (Stripe global)
- Localization and tax (VAT)

## Out of Scope

- Phase 1 Nigeria launch requirements (already in Volume 11)

---

## 1. Expansion Sequence

| Phase | Region | Trigger |
|-------|--------|---------|
| 1 | Nigeria | Launch |
| 1b | Kenya, Ghana | Payment + DPA readiness |
| 2 | Francophone West Africa | French UI |
| 3 | EU/UK merchants | GDPR tier, Stripe EU |
| 4 | US | SOC 2, CCPA awareness |

---

## 2. GDPR Activation Checklist

| Requirement | SCP Implementation |
|-------------|-------------------|
| Lawful basis | Consent + contract records |
| DPO | EU representative if required |
| RoPA | Extended for EU processing |
| DSR tooling | Export/delete (NFR-077) |
| SCCs | For US subprocessors (AI, Cloudflare) |
| Breach 72h | Extend runbook to EU SA |
| DPIA | AI and profiling features |

---

## 3. Data Residency Options (Phase 3+)

| Tier | Primary storage | EU merchant option |
|------|-----------------|-------------------|
| Standard | Nigeria (Lagos) | SCC + transfer assessment |
| EU Enterprise | EU region (Frankfurt/Dublin) | Local residency |
| Hybrid | Compute NG, EU DB replica | Contract-specific |

---

## 4. Technical Workstreams

1. **Locale** — EUR/GBP, VAT rules, date formats
2. **Payments** — Stripe EU, SCA/3DS flows
3. **Search/legal** — Cookie consent banner (IAB TCF optional later)
4. **Support** — EU business hours overlap
5. **Observability** — EU log residency for EU tenants

---

## 5. Risks

| Risk | Mitigation |
|------|------------|
| Regulatory complexity | Legal counsel per region; don't launch without checklist |
| Fragmented infra cost | Enterprise tier funds dedicated regions |
| AI subprocessor transfers | EU AI Act awareness; DPIA |

---

## 6. Acceptance Criteria

1. EU test merchant completes signup with GDPR consent flow.
2. Data export includes all personal data categories per Art. 15.
3. Deletion propagates to search index and backups within policy window.
4. SCCs documented for all US subprocessors in RoPA.

---

## Sources

- GDPR text: https://eur-lex.europa.eu/eli/reg/2016/679/oj
- Nigeria NDPA cross-border §41–43 (Volume 11)
- NFR-072
