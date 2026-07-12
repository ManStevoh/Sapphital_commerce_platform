# Chapter 18: AI Memory, Knowledge Engine & Business Graph

**Document ID:** SCP-AI-001-18  
**Version:** 1.0.0  
**Status:** ✅ Active  
**Traceability:** ADR-020, FR-AI-002, FR-AI-007, NFR-040  

---

## Purpose

Specify **memory tiers**, the **Knowledge Engine (RAG)**, and the **AI Business Graph** — enabling AI to understand merchant businesses, not just answer isolated questions.

---

## 1. Memory Architecture

| Layer | TTL | Contents | Example |
|-------|-----|----------|---------|
| Short-term | Turn | Last N messages, tool results | Current chat |
| Session | Session | Cart, browse path, intent | "Looking for TV under KSh 80K" |
| Merchant | Persistent | Brand voice, policies, tone, vertical | Pharmacy compliance tone |
| Long-term | Persistent + retention policy | Embeddings, facts, docs | Warranty PDF indexed |
| Timeline | Append-only | Decisions, campaigns, anomalies | "March sales spike — Facebook ads" |
| Global | — | General world knowledge | Via LLM; not tenant-stored |

### Merchant Memory Schema (summary)

```text
ai_merchant_profile: tone, personality, languages, vertical, approval_rules
ai_memory_facts: key-value facts extracted from conversations (tenant-scoped)
ai_memory_timeline: event_type, summary, refs[], created_at
```

### AI Personalities

| Vertical | Voice |
|----------|-------|
| Luxury | Elegant, minimal, premium |
| Restaurant | Friendly, warm, fun |
| School | Professional, educational |
| Corporate B2B | Formal, technical |

Configured in merchant settings; injected into system prompts.

---

## 2. Knowledge Engine

### Ingestion Pipeline

```text
Upload (PDF, DOCX, CSV) → OCR if needed → chunk (512–1024 tokens) → embed → pgvector
Product/CMS changes → webhook → re-embed affected docs
```

### Retrieval

- Hybrid: vector similarity + keyword (BM25) for SKUs and codes
- Always filter: `tenant_id`, optional `store_id`
- Cite sources in customer-facing answers when policy requires

### Use Cases

| Question | Source |
|----------|--------|
| "How long is warranty?" | Uploaded policy PDF |
| "Return damaged item?" | Merchant return policy + order RAG |
| "Dosage for Product X?" | **Escalate** — regulated vertical guardrail |

---

## 3. Digital Twin (Phase 2+)

Beyond storing rows, SIP maintains an **operational model** per merchant:

| Signal | Source modules |
|--------|----------------|
| Sales trends | Analytics |
| CLV, segments | CRM |
| Inventory turnover | Inventory |
| Payment failure patterns | FSL |
| Marketing ROAS | Marketing |
| Seasonal patterns | Analytics + timeline |

Digital twin feeds: Analytics Agent, Decision Engine, morning Copilot briefing, Business Simulator.

**Privacy:** Twin data never leaves tenant boundary; no cross-tenant twin access.

---

## 4. AI Business Graph

Graph representation for relationship reasoning:

```text
Customer → Orders → Products → Categories → Suppliers → Warehouses → Payments → Campaigns
```

Storage: PostgreSQL relational core + optional graph projection (`ai_business_edges` Phase 3) for multi-hop queries.

Enables: "Which customers who bought X also respond to SMS campaigns?" without brittle SQL chains in prompts.

---

## 5. Memory Timeline

Searchable history for questions like *"Why did sales increase in March?"*

| Event type | Trigger |
|------------|---------|
| `campaign_launched` | Marketing module |
| `price_changed` | Catalog |
| `inventory_stockout` | Inventory |
| `payment_gateway_switched` | FSL |
| `ai_recommendation_accepted` | Intelligence |

---

## 6. DSAR & Retention

- Memory deletion honors NDPA DSAR within 24h
- Timeline and facts redacted; embeddings purged by `document_id`
- Merchant memory export available (data portability)

---

## 7. Acceptance Criteria

- [ ] Six memory tiers documented and tenant-scoped
- [ ] PDF upload → queryable within 5 min p95
- [ ] Business graph links customer → order → product
- [ ] Personality affects generated copy tone
- [ ] Timeline records major business events

---

## References

- [Ch. 03 — RAG & pgvector](./03-rag-pgvector.md)
- [Ch. 22 — Digital Twin & Copilot](./22-advanced-ai-capabilities.md)
