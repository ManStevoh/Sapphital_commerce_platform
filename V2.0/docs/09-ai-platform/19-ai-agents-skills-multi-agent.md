# Chapter 19: AI Agents, Skills & Multi-Agent Orchestration

**Document ID:** SCP-AI-001-19  
**Version:** 1.0.0  
**Status:** ✅ Active  
**Traceability:** ADR-020, FR-AI-006, FR-AI-015  

---

## Purpose

Catalog **specialized AI agents**, the **Skills Marketplace**, and **multi-agent collaboration** patterns — including debate/consensus for complex decisions.

---

## 1. Agent Personas (Four Surfaces)

| Surface | Agent | Primary users |
|---------|-------|---------------|
| **Customer AI** | Shopping Assistant, Voice Shopper | Shoppers |
| **Merchant AI** | Ops Copilot, Commerce Advisor | Store owners |
| **Admin AI** | Platform support, fraud review assist | Sapphital staff |
| **Developer AI** | API assistant, theme scaffolder | Partners |

---

## 2. Business Agents Catalog

| Agent | Creates / Monitors | Tools (examples) |
|-------|-------------------|------------------|
| **Catalog** | Products, variants, SEO, tags, collections | `create_product`, `suggest_category` |
| **Marketing** | Ads, email, SMS, social, landing pages | `draft_campaign`, `schedule_post` |
| **Inventory** | Stock, reorder, demand forecast | `get_stock_levels`, `suggest_reorder` |
| **Analytics** | Sales drops, bottlenecks, forecasts | `query_analytics`, `explain_metric` |
| **Support** | Returns, tracking, FAQs, escalation | `get_order`, `initiate_return` |
| **Finance** | Revenue, tax, cash flow, invoices | `get_ledger_summary`, `explain_tax_line` |
| **Theme Designer** | Sections, colors, typography, layout | `propose_theme_sections` |
| **Payment Advisor** | Gateway recommendation | `list_gateways`, `failure_report` |
| **SEO** | Meta, schema, content gaps | `audit_seo`, `generate_meta` |
| **Security** | Fraud, anomalies, bot patterns | `flag_order`, `explain_risk` |
| **Shipping** | Rates, courier selection | `quote_shipping`, `track_shipment` |

All agents share: gateway, memory, knowledge, safety pipeline, audit.

---

## 3. Multi-Agent Orchestration

### Sequential (Store Build)

```text
Orchestrator → Theme → Catalog → SEO → Payment → Tax → Shipping → Marketing → Done
```

### Parallel + Merge

Independent agents run concurrently; orchestrator merges outputs (e.g., homepage sections from Theme + Marketing).

### Multi-Agent Debate (Phase 3)

Complex recommendation:

```text
Pricing Agent ──┐
Marketing Agent ├──→ Consensus Engine → Explainable recommendation → Merchant
Inventory Agent ─┤
Finance Agent ───┘
```

Consensus includes: recommendation, reasoning, confidence, dissent notes.

---

## 4. AI Skills Marketplace (Phase 3)

Third-party **skills** = packaged agent + tools + prompts + vertical knowledge.

| Skill | Vertical |
|-------|----------|
| Pharmacy AI | Prescription workflows, compliance copy |
| Restaurant AI | Menu, reservations, dietary tags |
| Agriculture AI | Seasonal pricing, bulk MOQ |
| School AI | Fees, uniforms, enrollment |
| Real Estate AI | Listings, viewing bookings |

Install flow: merchant browses marketplace → one-click enable → entitlements + billing hook.

Developers publish via Volume 12 plugin SDK; review gate for safety and data access scope.

---

## 5. Developer AI Agent

Assists platform builders:

- Generate Laravel modules and tests
- Explain REST/GraphQL endpoints
- Scaffold themes and sections
- Review architecture against ADRs
- Produce documentation drafts

Sandbox tenant only for destructive tools.

---

## 6. Human-in-the-Loop Matrix

| Action | Gate |
|--------|------|
| Generate draft copy | Auto; merchant edits |
| Publish product/theme | Merchant confirm |
| Change price | Merchant confirm |
| Issue refund | Merchant confirm |
| Enable new gateway | Merchant confirm |
| Auto-reorder inventory | Configurable auto vs suggest |

---

## 7. Acceptance Criteria

- [ ] Agent catalog covers 11+ business domains
- [ ] Orchestrator runs multi-step store build workflow
- [ ] Multi-agent debate produces explainable consensus (Phase 3)
- [ ] Skills marketplace install enables agent bundle
- [ ] Developer agent scoped to sandbox + read-only prod APIs

---

## References

- [Ch. 04 — Agent Orchestration](./04-agent-orchestration.md)
- [Ch. 05–08 — Individual agents](./05-shopping-assistant-agent.md)
- [Ch. 16 — Africa Commerce AI Advisor](./16-africa-commerce-ai-advisor.md)
