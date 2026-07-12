# Chapter 12: Merchant Storefront Analytics

**Document ID:** SCP-OPS-001-12  
**Version:** 1.0.0  
**Status:** ✅ Active  
**Traceability:** NFR-062, Vol 9 AnalyticsAgent  
**Legacy mapping:** `Modules/SiteAnalytics`

---

## Purpose

**Merchant-facing** page and conversion analytics — distinct from platform observability (Vol 10/14).

## Scope

- Page views, unique visitors (cookie-less fingerprint hash + consent)
- Top products viewed, search terms
- Cart abandonment funnel (basic)
- Traffic sources (UTM)
- Admin dashboard widgets

## Out of Scope

- Replacing Google Analytics (Integrations hub complements)

---

## 1. Data Model

| Table | Grain |
|-------|-------|
| `analytics_page_views` | store_id, path, day, count |
| `analytics_product_views` | product_id, day, count |
| `analytics_events` | `add_to_cart`, `begin_checkout`, `purchase` |

Aggregated nightly; raw events 90-day retention (NDPA).

---

## 2. Consent

Cookie banner (Vol 20) gates non-essential tracking. Essential: session cart only.

---

## 3. Merchant UI

**Analytics → Overview** — 7/30 day charts, top pages, top products, conversion rate.

Export PDF weekly summary (Phase 2).

---

## 4. Acceptance Criteria

- [ ] Page view increments do not block storefront (async beacon)
- [ ] Opt-out respects consent
- [ ] Cross-tenant analytics leak impossible (RLS)
- [ ] Dashboard loads ≤ 3s with 1M aggregated rows

---

## References

- [Vol 19 Ch. 11 — Integrations Hub](../19-automation-integrations/11-merchant-integrations-hub.md)
- [Platform/Analytics/](../03-architecture/13-platform-os-architecture.md)
