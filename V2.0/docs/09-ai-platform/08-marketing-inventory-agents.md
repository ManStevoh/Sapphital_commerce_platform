# Chapter 08: Marketing & Inventory Agents

**Document ID:** SCP-AI-001-08  
**Version:** 1.0.0  
**Status:** 📝 Draft  
**Traceability:** FR-AI-015, NFR-008, NFR-041  

---

## 1. Purpose

Define **marketing** and **inventory** agents that help merchants grow revenue and avoid stockouts — common pain points for Nigerian retailers facing supply chain delays, seasonal demand spikes (Detty December, Eid, Easter), and manual Excel-based forecasting.

## 2. Scope

- Marketing Agent: campaign copy, email drafts, social snippets, promotion ideas
- Inventory Agent: stock alerts, reorder suggestions, demand commentary
- Scheduling and background analysis jobs
- Approval workflows

## 3. Out of Scope

- Autonomous ad spend on Meta/Google (Phase 3 integration)
- Supplier PO transmission (ERP Phase 3)
- Fraud scoring (Commerce risk module)

## 4. Marketing Agent

### Capabilities

| Capability | Output | Gate |
|------------|--------|------|
| Email campaign draft | Subject + body HTML/markdown | Merchant approve |
| SMS copy (160 chars) | Text | Merchant approve |
| Social post | Instagram/Facebook caption | Merchant approve |
| Promotion idea | Structured proposal | Merchant approve |
| Product spotlight | Featured collection suggestion | Draft only |

### Tools

| Tool | Risk |
|------|------|
| `list_active_promotions` | read |
| `get_segment_summary` | read (aggregated, no individual PII) |
| `propose_campaign_email` | draft |
| `propose_sms_campaign` | draft |
| `search_products` | read |

### Business Rules

| ID | Rule |
|----|------|
| BR-MA-01 | No guaranteed ROI claims ("100% sales boost") |
| BR-MA-02 | SMS must include opt-out language per Nigeria telecom norms |
| BR-MA-03 | Cannot target segments merchant lacks consent for (NDPA) |
| BR-MA-04 | Content must respect merchant brand voice setting |
| BR-MA-05 | Pidgin campaigns only when `locale=pcm-NG` explicitly selected |

### Nigeria Context

- Seasonal templates: Detty December, Salah, Easter, Back to School
- NGN price formatting in all copy
- Mobile-first email length assumptions

## 5. Inventory Agent

### Capabilities

| Capability | Trigger | Output |
|------------|---------|--------|
| Low stock alert | `inventory below reorder_point` | Notification + explanation |
| Reorder suggestion | Weekly job | SKU list + suggested qty |
| Dead stock flag | 90d no sales | Report section |
| Demand spike explanation | Anomaly detection | Narrative + data refs |

### Tools

| Tool | Risk |
|------|------|
| `get_inventory_snapshot` | read |
| `get_sales_velocity` | read |
| `propose_inventory_adjustment` | write |
| `propose_reorder_quantity` | draft |

### Business Rules

| ID | Rule |
|----|------|
| BR-IA-01 | Reorder suggestions use 30d velocity; disclose assumptions |
| BR-IA-02 | No auto-PO; merchant confirms quantities |
| BR-IA-03 | Warehouse-scoped data in multi-warehouse tenants |
| BR-IA-04 | Alert fatigue cap: max 20 SKUs per daily digest |

### Algorithms (Phase 2)

```text
suggested_reorder = max(0, (avg_daily_sales × lead_time_days) + safety_stock - on_hand)
```

`safety_stock` default 7 days sales; merchant configurable.

**Assumption:** Lead time manual input Phase 2; supplier integration Phase 3.

## 6. Shared Orchestration

Both agents use `agent_type` specific prompts but share:

- Model gateway `quality` preference for long drafts
- RAG over product catalog
- Approval gate for all outbound customer communications

## 7. Background Jobs

| Job | Schedule |
|-----|----------|
| `InventoryWeeklyAnalysisJob` | Monday 06:00 tenant TZ |
| `MarketingSeasonalSuggestionJob` | 1st of month |
| `DeadStockScanJob` | Weekly |

## 8. Events

- `MarketingDraftProposed`, `MarketingCampaignSent` (on human send)
- `InventoryAlertRaised`, `ReorderSuggestionGenerated`

## 9. Entitlements

| Plan | Marketing Agent | Inventory Agent |
|------|-----------------|-----------------|
| Starter | Email drafts only | Low stock alerts |
| Growth | + SMS, social | + weekly reorder report |
| Enterprise | + segment ideas | + multi-warehouse |

## 10. Observability

- Campaign draft acceptance rate
- Inventory alert → merchant action rate
- Token cost per agent_type (Ch. 10)

## 11. Security & Compliance

- Marketing segments: aggregated counts only in prompts
- No customer names in marketing agent context unless explicit 1:1 template with consent
- NDPA: record lawful basis `legitimate_interest` or `consent` per campaign type in RoPA

## 12. Test Strategy

- Verify SMS length and opt-out footer
- Inventory math unit tests with fixture SKUs
- Consent gate: blocked segment without marketing consent

## 13. Acceptance Criteria

- [ ] Marketing drafts require explicit send approval
- [ ] Inventory alerts respect reorder_point from Commerce
- [ ] Seasonal templates available for Nigeria calendar
- [ ] No individual PII in marketing agent prompts
- [ ] Weekly inventory job completes within NFR-008 p95

## 14. Sources

- Nigeria retail seasonality (E3 practitioner patterns)
- Inventory reorder formulas (standard operations research)
