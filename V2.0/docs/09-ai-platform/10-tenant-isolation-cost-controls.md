# Chapter 10: Tenant Isolation & Cost Controls

**Document ID:** SCP-AI-001-10  
**Version:** 1.0.0  
**Status:** 📝 Draft  
**Traceability:** FR-AI-008, FR-AI-012, NFR-040, NFR-036, ADR-002, ADR-005  

---

## 1. Purpose

Ensure AI workloads respect **tenant boundaries** at every layer and consume resources predictably through **entitlements, metering, budgets, and throttling** — preventing noisy-neighbor token spend and cross-merchant data leakage.

## 2. Scope

- Isolation enforcement points
- Plan entitlements
- Usage metering and billing integration
- Budgets and throttles
- Fair scheduling
- Platform admin controls

## 3. Out of Scope

- Subscription pricing tables (Volume 7 Billing)
- Invoice PDF generation

## 4. Isolation Enforcement Stack

| Layer | Control |
|-------|---------|
| API gateway | `tenant_id` from auth context only |
| Orchestrator | Reject body-supplied tenant mismatch |
| RAG | SQL `tenant_id` bind mandatory |
| Memory | `tenant_id` + `subject_id` composite |
| Cache | Key prefix `ai:{tenant_id}:` |
| Queue | `TenantAwareJob` sets `SET LOCAL` |
| Logs/traces | `tenant_id` attribute required |
| Embeddings | RLS on `ai_embeddings` |

### Isolation test cases (CI blocking)

```text
IT-AI-01: Tenant A conversation cannot load Tenant B messages
IT-AI-02: Tenant A RAG query returns zero Tenant B chunks
IT-AI-03: Tenant A tool call with Tenant B order_id → 404
IT-AI-04: Tenant A cache poison does not affect Tenant B
IT-AI-05: Platform admin without impersonation cannot query merchant AI memory
```

## 5. Entitlements by Plan

| Entitlement | Starter | Growth | Enterprise |
|-------------|---------|--------|------------|
| AI enabled | Yes | Yes | Yes |
| Monthly AI tokens (in+out) | 500K | 2M | 10M (negotiable) |
| Shopping assistant | Yes | Yes | Yes |
| Merchant copilot | Yes | Yes | Yes |
| Support assist | — | Yes | Yes |
| Marketing agent | — | Yes | Yes |
| Inventory agent | — | Yes | Yes |
| Model tier | fast | auto | quality + custom |
| Concurrent sessions | 10 | 30 | 100 |
| Custom prompt overrides | — | — | Yes |

Overage: soft cap → throttle; hard cap → AI disabled until next cycle or top-up (Volume 7).

## 6. Usage Metering

### Ledger schema (`ai_usage_ledger`)

Written synchronously after each gateway completion.

| Field | Billing Use |
|-------|-------------|
| `tokens_in`, `tokens_out` | Primary meter |
| `cost_usd_micros` | COGS tracking |
| `agent_type` | Feature analytics |
| `billable` | Exclude platform eval |

### Aggregation

`AggregateAIUsageJob` hourly → `tenant_ai_usage_daily` rollups for dashboard and billing export.

## 7. Budgets & Throttles

| Control | Default | Behavior |
|---------|---------|----------|
| Daily token budget | 20% of monthly / 30 | 80% warning email; 100% throttle to read-only tools |
| Per-minute requests | 60 / tenant | 429 with Retry-After |
| Per-session messages | 100 customer | Graceful close |
| Embedding reindex | 1/hour | Queue reject |
| Max tool steps | 5 | Orchestrator enforced |

**Throttle mode:** Agent may answer questions but cannot invoke `write`/`financial` tools.

## 8. Fair Scheduling

Queue priority by plan:

```text
Enterprise > Growth > Starter > Platform internal
```

Within tier, FIFO. Prevent single tenant monopolizing embedding workers:

- Max 2 concurrent embedding jobs per tenant
- Global worker pool size configured in Horizon

## 9. Cost Controls for Platform

| Dashboard (Platform Admin) | Purpose |
|----------------------------|---------|
| Top tenants by AI spend | Finance review |
| Provider cost breakdown | Negotiate contracts |
| Fallback rate | Reliability |
| Gross margin per plan | Pricing validation |

**Alert:** Tenant projects to exceed 150% monthly entitlement by day 20 → Customer success notification.

## 10. Merchant-Facing Dashboard

Settings → AI → Usage:

- Tokens used / limit (progress bar)
- Estimated NGN equivalent cost (informational)
- Breakdown by agent
- Toggle disable AI module-wide (FR-AI-012)

## 11. Events

- `AIUsageThresholdWarning` (80%, 95%, 100%)
- `AIThrottled`
- `AIDisabledByMerchant`
- `AIReenabled`

## 12. Security

- Usage API owner-only; staff see read-only breakdown without costs if tenant setting enabled
- No tenant can query another tenant's usage via IDOR
- Rate limits align NFR-036

## 13. Observability

Metrics:

- `ai_tenant_tokens_total{tenant_id, agent_type}` (cardinality capped for Prometheus — top N + aggregate)
- `ai_throttle_total{reason}`
- `ai_isolation_violation_total` (must stay 0)

## 14. Operational Runbooks

| Scenario | Action |
|----------|--------|
| Runaway spend attack | Disable tenant AI flag + WAF IP block |
| Provider bill 2× forecast | Tighten default `max_tokens`; notify merchants |
| Embedding backlog | Scale workers; pause low-priority reindex |

## 15. Test Strategy

- Entitlement enforcement per plan in feature tests
- Throttle at 100% budget → write tools blocked
- Isolation suite in parallel CI job

## 16. Acceptance Criteria

- [ ] 0 isolation test failures on `main`
- [ ] Usage ledger matches provider invoice ±2% monthly
- [ ] Throttle activates within 1 request of budget breach
- [ ] Merchant disable stops all agent endpoints within 5s
- [ ] Plan downgrade removes agents on next request

## 17. Sources

- ADR-002 Multi-tenancy
- Volume 7 Billing entitlements (cross-ref)
- SaaS fair scheduling patterns (E3)
