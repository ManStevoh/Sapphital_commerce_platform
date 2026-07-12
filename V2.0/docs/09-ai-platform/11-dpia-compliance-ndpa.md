# Chapter 11: DPIA & NDPA Compliance

**Document ID:** SCP-AI-001-11  
**Version:** 1.0.0  
**Status:** 📝 Draft  
**Traceability:** NFR-083, NFR-085, FR-AI-014, Volume 11 Ch. 02  

---

## 1. Purpose

Document **Data Protection Impact Assessment (DPIA)** requirements and **Nigeria NDPA 2023 + GAID 2025** controls specific to SCP's AI platform — including automated decision-making, profiling, cross-border model inference, memory storage, and data subject rights.

## 2. Scope

- Lawful basis mapping per AI feature
- DPIA template and triggers
- RoPA entries for AI processing
- Cross-border transfers to model providers
- Automated decision-making boundaries
- Consent and transparency
- DSAR handling for AI data
- DPO oversight

## 3. Out of Scope

- Full platform RoPA (Volume 11)
- Legal wording of Privacy Policy
- Kenya ODPC forms (mirror structure at KE launch — NFR-084)

## 4. Regulatory Context (Nigeria)

| Instrument | AI Relevance |
|------------|--------------|
| NDPA 2023 | Lawful processing, data minimization, security, cross-border transfer |
| GAID 2025 | Registration tiers, DPO, compliance audit report (CAR), breach notification |
| NDPC Guidance | DPIA for high-risk processing including new technologies |

SCP as processor/controller: Platform is **controller** for merchant account data; **processor** for end-customer data processed on merchant instructions via storefront AI.

## 5. Lawful Basis Matrix

| Processing Activity | Controller | Lawful Basis | Notes |
|---------------------|------------|--------------|-------|
| Shopping assistant conversation | Merchant (customer data) / SCP (telemetry) | Legitimate interest + consent banner | Consent for marketing memory |
| Merchant copilot | SCP (merchant staff data) | Contract | Staff identifiable |
| Support assist | Merchant | Legitimate interest | Ticket content |
| Embeddings (catalog) | Merchant | Contract | No customer PII |
| AI usage metering | SCP | Contract | Billing |
| Safety moderation logs | SCP | Legitimate interest | Security |
| Long-term memory facts | Merchant | Consent (explicit) | Opt-in per fact |
| Model provider inference | SCP/merchant | Contract + transfer safeguard | Subprocessor |

**Transparency:** Storefront AI widget links to merchant privacy policy + SCP AI subprocessor notice.

## 6. DPIA Triggers

DPIA required before GA when:

1. New agent type processes personal data at scale
2. Systematic profiling affecting data subjects
3. Automated decisions with legal/similarly significant effects
4. Large-scale processing of sensitive categories (health, biometrics) — **prohibited in SCP AI Phase 1**
5. Cross-border transfer combined with innovative technology

### DPIA Register (AI Platform)

| DPIA ID | Feature | Status | Review Date |
|---------|---------|--------|-------------|
| DPIA-AI-001 | Shopping Assistant (customer chat) | Required Phase 1 | Annual |
| DPIA-AI-002 | Merchant Ops Copilot | Required Phase 1 | Annual |
| DPIA-AI-003 | Support Agent (deflect) | Required Phase 1 | Annual |
| DPIA-AI-004 | Long-term memory | Required Phase 1 | Annual |
| DPIA-AI-005 | Marketing Agent | Phase 2 | Before GA |
| DPIA-AI-006 | Inventory Agent | Phase 2 | Before GA |

## 7. DPIA Template (Mandatory Sections)

Each AI DPIA document must include:

1. **Processing description** — what data, whose, why
2. **Necessity & proportionality** — why AI vs non-AI alternative
3. **Data flows** — diagram including model providers
4. **Risks to data subjects** — hallucination, bias, breach, discrimination
5. **Mitigations** — RAG grounding, human-in-the-loop, isolation, retention
6. **Residual risk rating** — low/medium/high with DPO sign-off
7. **Consultation** — DPO mandatory; NDPC if high residual (legal counsel)
8. **Approval & review schedule**

Stored in internal compliance repo; summary in RoPA.

## 8. Automated Decision-Making (ADM)

NDPA and GAID require scrutiny when decisions significantly affect individuals.

**SCP Phase 1–2 policy:**

| Decision | Automated? | Allowed |
|----------|------------|---------|
| Product recommendations in chat | Partial | Yes — informational only |
| Refunds | No | Human approval required |
| Credit/lending | N/A | Not offered |
| Account ban | No | Human review |
| Dynamic pricing per customer | No | Not in scope |
| Marketing segment inclusion | No | Human sends campaign |

Customers may request human review of AI-influenced shopping recommendations via support; logged as DSAR-adjacent request.

## 9. Profiling & Bias

| Risk | Control |
|------|---------|
| Price discrimination by ethnicity/inference | No demographic profiling in prompts |
| Language bias (Pidgin vs English quality) | Eval harness; disclose limitations |
| Regional bias (Lagos-centric training) | RAG uses merchant's own catalog only |
| Gender bias in product suggestions | Moderation + merchant feedback loop |

Annual bias review sample: 500 conversations stratified by locale.

## 10. Cross-Border Transfers

Model API calls transfer prompt content outside Nigeria.

| Requirement (NDPA §41–43) | SCP Implementation |
|---------------------------|-------------------|
| Adequate protection | Provider DPA + SCCs |
| Transparency | Subprocessor list on privacy page |
| Transfer records | `ai_transfer_log` per request metadata (no full prompt) |
| Data minimization | PII scrub pre-gateway |
| Enterprise option | Azure region pinning where available |

Align [ADR-011](../00-meta/adr/011-data-residency-africa.md): embeddings DB stays in-region; inference may cross border with record.

### Subprocessor Register (AI — Phase 1)

| Subprocessor | Purpose | Location | DPA |
|--------------|---------|----------|-----|
| OpenAI | Completions, embeddings | US | Yes |
| Anthropic | Fallback completions | US | Yes |
| Google | Fallback completions | US/EU | Yes |

## 11. Retention

| Data Type | Retention | Erasure |
|-----------|-----------|---------|
| `ai_messages` (customer) | 90 days default; merchant configurable 30–365 | DSAR pipeline |
| `ai_memory_facts` | Until delete or account closure | Customer portal |
| `ai_usage_ledger` | 7 years (billing) | Anonymize after |
| Safety flagged chats | 180 days | Manual review then delete |
| Embeddings (products) | Life of product | On product delete |

## 12. Data Subject Rights (DSAR)

| Right | AI Implementation |
|-------|-------------------|
| Access | Export includes conversations + memory facts JSON |
| Rectification | Memory fact edit API; corrections logged |
| Erasure | `EraseCustomerAIDataJob` cascades messages + memory |
| Restrict | Flag `ai_processing_restricted` — agents read-only, no memory write |
| Portability | JSON export machine-readable |
| Object | Opt-out disables shopping assistant memory |

**SLA:** 30 days maximum; target 14 days (NFR-077 alignment).

## 13. Consent UX

Storefront first open of AI widget:

```text
This assistant uses AI to help you shop. Conversations are processed per our
Privacy Policy. Some processing uses secure overseas AI providers.
[Continue] [No thanks]
```

Pidgin variant Phase 1. Memory opt-in separate: "You want make I remember your size?"

## 14. Breach Notification

AI-related breaches (prompt log leak, wrong tenant retrieval):

1. Contain within 1 hour
2. DPO assessment within 24 hours
3. NDPC notification within 72 hours if reportable
4. Merchant notification without undue delay

Runbook cross-ref Volume 11 Incident Response.

## 15. Children's Data

SCP storefronts are general audience. AI must not solicit age. If merchant sells children's products, no profiling of minors; parent purchases assumed.

## 16. DPO & CAR

- DPO reviews all AI DPIAs before GA
- RoPA updated within 5 business days of new agent
- Biannual compliance audit report includes AI section if DCPMI tier applies

## 17. RoPA Entry Template (AI)

```markdown
### Processing: Shopping Assistant
- Purpose: Customer product guidance
- Categories: Identity (optional), behavior (chat), preferences (memory opt-in)
- Recipients: SCP staff (support), model subprocessors
- Transfers: US (OpenAI) — SCC
- Retention: 90 days
- Security: TLS 1.3, RLS, encryption at rest
- DPIA: DPIA-AI-001 v1.0
```

## 18. Acceptance Criteria

- [ ] DPIA-AI-001 through 004 approved by DPO before Nigeria GA
- [ ] Subprocessor list includes all model providers
- [ ] DSAR erasure job clears AI tables verified E2E
- [ ] Consent banner live on storefront AI widget
- [ ] Transfer log populated for inference calls
- [ ] No prohibited automated financial decisions

## 19. Sources

- Nigeria NDPA 2023: https://ndpc.gov.ng/
- NDPC GAID 2025 (E1)
- ICO DPIA guidance (methodology reference, E2): https://ico.org.uk/for-organisations/uk-gdpr-guidance-and-resources/accountability-and-governance/data-protection-impact-assessments-dpias/
- Volume 11 Africa Regulatory Compliance
