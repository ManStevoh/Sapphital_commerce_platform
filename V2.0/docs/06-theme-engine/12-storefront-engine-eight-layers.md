# Chapter 12: Storefront Engine — Eight Experience Layers

**Document ID:** SCP-THE-006-12  
**Version:** 1.0.0  
**Status:** ✅ Active  
**Traceability:** ADR-017, ADR-018, PRD-004, PRD-AI-001, NFR-001, NFR-047  

---

## Purpose

Define the **SAPPHITAL Storefront Engine** — the platform layer that transforms SCP from “a place to display products” into a **living digital salesperson**. This chapter maps the eight experience layers, their ownership, and phase rollout.

## Design Thesis

> If Shopify started in 2026, the storefront would not be a static theme — it would be an **intelligent, adaptive, multi-modal commerce experience** with the merchant always in control.

Legacy platforms optimize catalog display. SCP optimizes **relevance, trust, conversion, and return visits** through layered capabilities that compose without breaking performance or compliance.

---

## 1. Layer Model

```text
┌─────────────────────────────────────────────────────────┐
│              Customer Experience (outcome)               │
├─────────────────────────────────────────────────────────┤
│ 1. Storefront Experience    — Pages, nav, PDP, cart UI  │
│ 2. Commerce Experience      — Pricing, checkout, account│
│ 3. Content Experience       — Story, CMS, SEO, media    │
│ 4. AI Experience            — Search, advisor, compare  │
│ 5. Marketing Experience     — Promos, loyalty, segments  │
│ 6. Community Experience     — Q&A, UGC, follows, lists  │
│ 7. Performance Experience   — Speed, SEO, CWV, cache    │
│ 8. Intelligence Experience  — ASI, personalization rules│
└─────────────────────────────────────────────────────────┘
```

Each layer has a **primary owner module** and **Storefront Runtime integration point**.

| Layer | Owner volume | Runtime integration |
|-------|--------------|---------------------|
| Storefront | Vol 4, Vol 6 | RSC templates, sections |
| Commerce | Vol 5 | Storefront API, checkout |
| Content | Vol 7 | CMS entries → sections |
| AI | Vol 9 | Lazy widgets, search API |
| Marketing | Vol 5, Vol 19 | Price rules, campaigns |
| Community | Vol 5 Ch. 15, Vol 8 | UGC, Q&A APIs |
| Performance | Vol 4 Ch. 12, Vol 6 Ch. 14 | CDN, ISR, budgets |
| Intelligence | Vol 9 Ch. 14, ADR-018 | ASI snapshot, segments |

---

## 2. Layer 1 — Storefront Experience

**Goal:** Agency-quality presentation with composable sections (Volume 4 Ch. 13).

| Capability | Phase | Notes |
|------------|-------|-------|
| Composable homepage/PDP | 1 | Theme Engine JSON |
| Mega menu + mobile bottom nav | 1.1 | Collision rules Vol 6 Ch. 11 |
| Product Card + Quick View | 1 | `@scp/commerce-ui` |
| Interactive sections (countdown, before/after) | 2 | Merchant-enabled, perf-gated |
| AR preview hooks | 4 | Optional section types |
| Live shopping embed | 4 | Volume 15 H4 |

---

## 3. Layer 2 — Commerce Experience

**Goal:** Fintech-grade checkout and transparent pricing (Volume 5, ADR-004).

| Capability | Phase |
|------------|-------|
| Guest + account checkout | 1 |
| One-page checkout (mobile) | 1 |
| Express checkout (returning customer) | 1.1 |
| Dynamic pricing display (rules from backend) | 2 |
| Wholesale / VIP / member price tiers | 2–3 |
| Customer portal (orders, returns, wallet) | 2 |

Checkout layout remains **platform-locked** for PCI SAQ A; express paths use saved tokens via PSP.

---

## 4. Layer 3 — Content Experience

**Goal:** Apple-style storytelling, not SKU-only pages (Volume 7, ADR-012).

| Capability | Phase |
|------------|-------|
| Hero + video story sections | 1 |
| PDP narrative blocks (features, specs, reviews) | 1 |
| Blog + editorial collections | 2 |
| Structured FAQ + policy pages | 1 |
| Localized content (ADR-015) | 2 |

Product pages support **immersive sequence:** lifestyle media → headline → video → features → testimonials → specs → reviews → accessories → purchase.

---

## 5. Layer 4 — AI Experience

**Goal:** AI integrated into every interaction — not a corner chatbot (Volume 9 Ch. 13).

| Capability | Phase |
|------------|-------|
| Intent search (“laptop for software engineering”) | 2 |
| Embedded product finder | 2 |
| AI product comparison | 2 |
| Personal shopper (returning customer) | 2 |
| Voice shopping (mobile web + app) | 3 |
| Visual search (image upload) | 3 |
| PDP grounded Q&A | 2 |

All AI commerce surfaces use **live price/stock** from Commerce API; advice is grounded, not hallucinated.

---

## 6. Layer 5 — Marketing Experience

**Goal:** Segments, loyalty, and campaigns reflected automatically on storefront.

| Capability | Phase |
|------------|-------|
| Flash sales, coupons (Volume 5 Ch. 11) | 1 |
| Segment-specific homepage (VIP, first-time) | 2 |
| Loyalty points + tiers display | 2 |
| Referral rewards | 3 |
| Gamification (spin, challenges) — opt-in | 3 |
| Live auction / live pin commerce | 4 |

Merchants configure rules in admin; Runtime **reflects** rules — no duplicate pricing logic in themes.

---

## 7. Layer 6 — Community Experience

**Goal:** Engagement beyond transaction (Volume 5 Ch. 15).

| Capability | Phase |
|------------|-------|
| Product Q&A | 2 |
| Verified reviews + photo/video reviews | 2 |
| Wishlist + shareable gift lists | 1.1 |
| Follow brand / save collections | 3 |
| Social gallery (Instagram/TikTok cached) | 2 |
| Product votes / requests | 3 |

Community data is tenant-scoped with moderation (Volume 9 Ch. 09).

---

## 8. Layer 7 — Performance Experience

**Goal:** Invisible optimization — merchants never tune Web Vitals manually (Volume 6 Ch. 14).

Automatic platform duties:

- Responsive AVIF/WebP, lazy below-fold
- JS bundle splitting, prefetch likely routes
- ISR + CDN, structured data, meta generation
- Budget enforcement in Visual Builder Quality Coach
- RUM alerts to merchant when store degrades

---

## 9. Layer 8 — Intelligence Experience (ASI)

**Goal:** Store continuously improves with **transparent** merchant control (ADR-018, Volume 9 Ch. 14).

Examples:

- Homepage promotes rising-demand categories (proposal → accept)
- Collection sort weights conversion (opt-in auto-apply)
- Search suggestions improve from query logs
- CTA variants tested with explained results
- Recommendations adapt to consented browsing patterns

**Never silent.** Merchant inbox: “Suggest moving Best Sellers above Trending — conversion +12% last 14 days.”

---

## 10. Intelligent Homepage Segments

Runtime composes homepage from **segment recipes** — merchants do not manually build three homepages.

| Segment | Default module order |
|---------|---------------------|
| First-time visitor | Hero → Best sellers → Trending → Testimonials → Categories |
| Returning visitor | Continue shopping → Recently viewed → Recommended → Wishlist reminder → Flash sale |
| VIP / loyalty tier | Exclusive offers → Reward balance → Recommended → VIP collection → Early access |

Segment resolution order:

1. Explicit customer segment (loyalty tier, tags)
2. Session signals (returning, cart, wishlist)
3. Cold-start default (merchant preset)

All modules are standard sections; only **order and data source** change.

---

## 11. Three-System Implementation (ADR-017)

| System | Layers primarily implemented |
|--------|------------------------------|
| **Storefront Runtime** | 1, 2 (display), 4 (widgets), 7, 8 (read snapshot) |
| **Visual Builder** | 1 (structure), 3 (bind CMS), 5 (campaign slots), 8 (review ASI) |
| **Theme Engine** | 1 (components), section schemas, marketplace |

---

## 12. Phase Roadmap Summary

| Phase | Layers emphasized | Merchant outcome |
|-------|-------------------|------------------|
| **1 — Nigeria GA** | Storefront, Commerce, Content (basic), Performance | Professional, fast store |
| **2 — Growth** | AI, Marketing segments, Community, ASI proposals | Intelligent salesperson |
| **3 — Platform** | Voice, visual search, gamification, theme AI gen | Leapfrog features |
| **4 — Omnichannel** | AR, live shopping, advanced loyalty | Full digital salesperson |

---

## 13. Acceptance Criteria

- [ ] Eight layers documented with owner modules and phase tags
- [ ] Intelligent homepage segment recipes implemented in Runtime
- [ ] ASI proposals require merchant accept before live apply (default)
- [ ] AI layer uses Commerce API for price/stock on all surfaces
- [ ] Performance layer meets Volume 4 Ch. 12 budgets without merchant action
- [ ] Three-system boundaries enforced in CI (no admin bundle in Runtime)
- [ ] Community and marketing rules render from backend — not theme-side logic

---

## References

- [ADR-017 — Three-System Architecture](../00-meta/adr/017-three-system-storefront-architecture.md)
- [ADR-018 — ASI](../00-meta/adr/018-adaptive-storefront-intelligence.md)
- [Volume 4 Ch. 13 — Visual Direction](../04-design-system/13-storefront-visual-direction.md)
- [Volume 9 Ch. 13 — AI Storefront Commerce](../09-ai-platform/13-ai-storefront-commerce.md)
