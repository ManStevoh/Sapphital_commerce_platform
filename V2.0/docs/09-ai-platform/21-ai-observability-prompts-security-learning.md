# Chapter 21: AI Observability, Prompts, Security & Learning

**Document ID:** SCP-AI-001-21  
**Version:** 1.0.0  
**Status:** ✅ Active  
**Traceability:** ADR-020, NFR-062–NFR-068, NFR-041  

---

## Purpose

Specify **AI observability**, **prompt versioning**, the **security pipeline**, and the **learning loop** — treating prompts like source code and AI like production infrastructure.

---

## 1. AI Observability Dashboard

Metrics exposed to platform admin and merchant (tier-gated):

| Metric | Target | Alert |
|--------|--------|-------|
| AI Health | % successful tasks | < 98% SEV2 |
| Avg response time | p95 ≤ 3.5s | > 5s |
| Model cost | Per tenant/day | Budget threshold |
| Successful tasks | % | — |
| Failed tasks | % | > 2% |
| Hallucination reports | Count | Trend up |
| Customer satisfaction | CSAT on AI turns | < 90% |

OpenTelemetry span tree per turn (Ch. 01 §14).

---

## 2. Prompt Versioning

Never hardcode prompts in application code.

```text
product_description_prompt v1.0 → v1.1 → v2.0
```

| Field | Purpose |
|-------|---------|
| `prompt_id`, `version`, `content`, `model_hint` | Identity |
| `eval_score`, `conversion_impact` | Quality |
| `status` | draft, active, deprecated |
| `rollback_to` | Previous version pointer |

Deployment: activate new version → A/B shadow eval → promote or rollback.

Stored in `ai_prompt_templates` with tenant override layer.

---

## 3. Security Pipeline

Every request:

```text
User → Permission check → Rate limit → Prompt guard → Sensitive data filter → Model → Output validation → Response
```

| Stage | Blocks |
|-------|--------|
| Permission | Cross-tenant, unauthorized tools |
| Rate limit | Abuse, cost runaway |
| Prompt guard | Injection, jailbreak patterns |
| PII filter | PAN, national ID in prompts |
| Output validation | Policy violations, leaked secrets |

See also [Ch. 09 — Safety & Moderation](./09-safety-moderation.md).

---

## 4. Explainability & Confidence

Every recommendation internally stores:

```json
{
  "recommendation": "Increase price 4%",
  "explanation": "Demand up 30d; competitors raised; inventory limited",
  "confidence": 0.94,
  "evidence": ["analytics:demand", "competitor:scan", "inventory:days_cover"]
}
```

`confidence < 0.7` → flag for human review in admin queue.

Customer-facing: no raw confidence; merchant-facing: show explanation always.

---

## 5. AI Learning Loop

Signals collected (tenant-scoped, privacy-safe):

| Signal | Use |
|--------|-----|
| Merchant accepts/rejects suggestion | Prompt tuning |
| CSAT thumbs | Router quality scores |
| Conversion after AI change | Experiment analysis |
| Hallucination report | RAG gap fix |

**Platform-wide marketplace intelligence (Phase 4):** Aggregated anonymized trends only — rising categories, seasonal demand, payment method mix. **Never** expose individual merchant data.

---

## 6. AI Security Agent

Continuous monitoring:

- Unusual login patterns
- Fraudulent orders
- Payment anomalies (FSL integration)
- Suspicious refunds
- Bot traffic
- Inventory manipulation

Actions: recommend, alert, auto-hold order (merchant-configured).

---

## 7. Acceptance Criteria

- [ ] Admin dashboard shows health, cost, latency, CSAT
- [ ] Prompt rollback in < 1 minute
- [ ] Security pipeline blocks injection test suite
- [ ] Recommendations include explanation + confidence
- [ ] Learning signals feed prompt eval job weekly

---

## References

- [Ch. 09 — Safety & Moderation](./09-safety-moderation.md)
- [Ch. 10 — Tenant Isolation & Cost Controls](./10-tenant-isolation-cost-controls.md)
