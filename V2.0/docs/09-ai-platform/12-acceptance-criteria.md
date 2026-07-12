# Chapter 12: Acceptance Criteria

**Document ID:** SCP-AI-001-12  
**Version:** 1.0.0  
**Status:** 📝 Draft  

---

Volume 9 is **complete for Phase 1 Nigeria launch** when all criteria below pass.

## 1. Platform Foundation

- [ ] `App\Domains\AI` module deployed with gateway, RAG, orchestrator services
- [ ] All `ai_*` tables migrated with RLS enabled (ADR-002, ADR-005)
- [ ] pgvector extension active; HNSW index on `ai_embeddings`
- [ ] FR-AI-001 through FR-AI-014 implemented and traced in tests

## 2. Model Gateway

- [ ] Single gateway interface; no direct provider calls from agents
- [ ] Primary + fallback provider verified in chaos test
- [ ] Token usage ledger within ±2% of provider monthly invoice
- [ ] Streaming SSE cancels upstream on client disconnect ≤ 500 ms
- [ ] Provider API keys in Vault/encrypted env only (ADR-007)

## 3. RAG Pipeline

- [ ] Product publish → searchable via RAG within 5 min p95
- [ ] Every agent answer citing products includes retrievable `source_id`
- [ ] Isolation test IT-AI-02 passes: zero cross-tenant chunks
- [ ] Reindex API rate-limited and owner-gated
- [ ] Nightly reconcile job runs without error > 99% of days

## 4. Agent Orchestration

- [ ] Tool registry enforces risk classes (read/draft/write/financial)
- [ ] Max 5 tool steps per turn enforced
- [ ] Financial tools blocked without human approval workflow
- [ ] `AIToolInvoked` audit events within 1s of execution
- [ ] Session + long-term memory behave per Ch. 04 rules

## 5. Shopping Assistant

- [ ] Storefront widget WCAG 2.2 AA verified
- [ ] English and Nigerian Pidgin modes available
- [ ] Price/stock answers 100% from tools or RAG (golden set 200 queries)
- [ ] `add_to_cart` requires explicit user confirmation UI
- [ ] Widget lazy-load meets NFR-009 bundle budget contribution ≤ 40 KB gz

## 6. Merchant Ops Agent

- [ ] Copilot drawer/sheet functional on 320px admin
- [ ] All catalog/inventory writes via draft approval only
- [ ] Analytics tools use template IDs only — no raw SQL
- [ ] Staff role PII masking verified in order details tool

## 7. Support Agent

- [ ] Assist mode suggestions include citations
- [ ] Customer deflect cannot access other customers' orders (IDOR suite)
- [ ] Refund proposals require human agent approval
- [ ] Escalation triggers after 3 failed deflection turns

## 8. Safety & Moderation

- [ ] Pre/post moderation blocks OWASP LLM injection suite (≥ 95% cases)
- [ ] PII scrubber covers Nigerian phone formats
- [ ] `AISafetyViolationDetected` reaches SIEM within 60s
- [ ] Cross-tenant exfiltration prompts return safe refusal

## 9. Tenant Isolation & Cost

- [ ] Isolation suite IT-AI-01 through IT-AI-05: **0 failures**
- [ ] Plan entitlements enforce token limits and agent availability
- [ ] 100% budget throttle blocks write/financial tools
- [ ] Merchant AI disable effective within 5 seconds
- [ ] Per-tenant rate limits return 429 with `Retry-After`

## 10. NDPA / DPIA (Launch Blockers)

- [ ] DPIA-AI-001 through DPIA-AI-004 signed by NDPC-certified DPO
- [ ] RoPA includes all Phase 1 AI processing activities
- [ ] Subprocessor page lists OpenAI, Anthropic, Google with transfer mechanism
- [ ] Storefront AI consent notice (English + Pidgin) deployed
- [ ] DSAR erasure E2E: customer export includes AI data; erasure clears `ai_messages` + `ai_memory_facts`
- [ ] `ai_transfer_log` records metadata for cross-border inference calls
- [ ] No automated refund/pricing ADM without human review

## 11. Observability

- [ ] OpenTelemetry spans: `ai.agent.turn` with children per Ch. 01
- [ ] Dashboards: token usage, tool errors, safety blocks, fallback rate
- [ ] P2 alert: fallback rate > 10% for 15 min — tested
- [ ] P1 alert: all providers circuit open — tested
- [ ] Structured logs include `tenant_id`, `trace_id`; prompts redacted in production

## 12. Performance

- [ ] First token p95 ≤ 800 ms (shopping assistant, 4G profile)
- [ ] Full turn with 1 tool p95 ≤ 3.5 s
- [ ] RAG retrieval p95 ≤ 120 ms
- [ ] Embed 1K products ≤ 10 min background

## 13. Localization (Phase 1 / 1.5 Gates)

| Gate | Criteria |
|------|----------|
| Phase 1 | English + Pidgin assistant verified (50 utterance golden set each) |
| Phase 1.5 | Hausa, Yoruba, Igbo precision@5 ≥ 0.70 on catalog search eval |

## 14. Marketing & Inventory (Phase 2 Gate)

- [ ] Marketing drafts require send approval; SMS opt-out present
- [ ] Inventory weekly job suggests reorder with documented formula
- [ ] DPIA-AI-005 and DPIA-AI-006 approved before enable

## 15. Documentation & Governance

- [ ] Volume 9 chapters reviewed by Lead Architect
- [ ] ADR-019 (AI gateway/runtime) recorded at implementation lock
- [ ] Intent search, comparison, and ASI proposal flows pass acceptance tests
- [ ] Volume 12 OpenAPI stubs for `/api/v1/ai/*` published
- [ ] Security module checklist (Volume 11) AI items signed off

## 16. Exceptional Conditions

- [ ] Missing `tenant_id` → AI request rejected 403 (fail-closed)
- [ ] Provider total outage → user sees graceful message, no stack trace
- [ ] Tool authorization denial → agent explains inability, no data leak
- [ ] Over-limit tenant → throttle mode, not silent failure

---

**Sign-off roles:** Lead Architect, DPO (Nigeria), AI feature lead (TBD), Security reviewer (TBD).

**Phase 1 Nigeria GA:** Sections 1–13, 15–16 required. Section 14 required for Phase 2 features only.
