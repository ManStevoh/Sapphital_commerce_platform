# ADR-018: Adaptive Storefront Intelligence (ASI)

**Date:** 2026-07-12  
**Status:** Accepted  
**Deciders:** Stephen Musyoka Makola  
**Volume Reference:** Volume 9 — AI Platform; Volume 6 — Storefront Engine

## Context

SCP aims to leapfrog legacy commerce by making the storefront a **living digital salesperson** — not a static product grid. Continuous optimization of homepage modules, product order, search suggestions, and CTAs can increase conversion, but **silent black-box changes** erode merchant trust and violate NDPA expectations around profiling.

Merchants in Nigeria and Africa need AI that **explains, proposes, and waits for approval** — especially where mobile traffic and WhatsApp-driven discovery dominate.

## Decision

**Implement Adaptive Storefront Intelligence (ASI)** as a governed optimization layer:

### 1. What ASI Optimizes (with merchant opt-in)

| Surface | Adaptation | Default phase |
|---------|------------|---------------|
| Homepage module order | Promote rising categories, best converters | Phase 2 |
| Product grid sort | Conversion-weighted within collection rules | Phase 2 |
| Search suggestions | Learn from anonymized query logs | Phase 2 |
| CTA copy variants | Controlled A/B with explanation | Phase 3 |
| Recommendation blocks | Personalization + segment rules | Phase 2 |
| Hero/campaign | Suggest swap when promotion underperforms | Phase 3 |

### 2. Transparency Contract (non-negotiable)

Every ASI proposal includes:

- **What** would change (section, sort, copy)
- **Why** (metric + time window, e.g. “Category X +22% views, 0 sales”)
- **Expected impact** (range, not guarantee)
- **Actions:** Accept | Reject | Customize | Snooze 7 days

**No silent publish.** Runtime applies ASI changes only after:

- Merchant explicit accept, OR
- Merchant enables “auto-apply low-risk suggestions” with defined scope (sort only, not new sections)

### 3. Data & Privacy

| Data use | Rule |
|----------|------|
| Aggregate analytics | Allowed for ASI training per tenant |
| Cross-tenant learning | Platform-level anonymized patterns only; never expose competitor data |
| Individual profiling | Requires consent banner; opt-out disables personalized ASI |
| PII in prompts | Never sent to external LLM without redaction |
| Audit | Every accept/reject logged (ADR-009) |

### 4. Architecture

- **ASI Worker** reads `analytics_*` tables + search logs (Volume 17)
- **Proposal API** writes to `storefront_asi_proposals` (tenant-scoped, RLS)
- **Visual Builder** renders proposal inbox and diff preview
- **Storefront Runtime** reads active `asi_overrides` snapshot at render time (cache-friendly)

### 5. Relationship to AI Agents

| Capability | Owner |
|------------|-------|
| Natural-language shopping | Shopping Assistant (Vol 9 Ch. 05) |
| Theme generation from prompt | AI Theme Generator (Vol 9 Ch. 15) |
| Layout/sort optimization | ASI (this ADR) |
| Product comparison narrative | AI Comparison Service (Vol 9 Ch. 13) |

## Alternatives Considered

| Alternative | Pros | Cons | Why Rejected |
|-------------|------|------|--------------|
| Fully automatic optimization | Higher short-term lift | Merchant distrust; regulatory risk | Contradicts transparency principle |
| No platform optimization | Simple | Misses leapfrog differentiation | Strategic gap |
| Third-party personalization SaaS | Fast | Data residency, cost, black box | NDPA + merchant control |
| Manual A/B only | Clear | Merchants lack time | ASI augments, not replaces |

## Consequences

### Positive

- Differentiated “store that learns” without Shopify-era static homepage
- Merchant remains decision-maker; aligns with Sapphital education brand
- ASI proposals reusable as merchant education (“why best sellers work”)

### Negative

- Proposal UX and explainability engineering cost
- Risk of over-notification; need snooze and digest modes
- Requires analytics maturity (Volume 17) before Phase 2 launch

## References

- [Volume 9 Ch. 14 — Adaptive Storefront Intelligence](../../09-ai-platform/14-adaptive-storefront-intelligence.md)
- [Volume 6 Ch. 12 — Eight Experience Layers](../../06-theme-engine/12-storefront-engine-eight-layers.md)
- NFR-083–NFR-085, ADR-009, ADR-017
