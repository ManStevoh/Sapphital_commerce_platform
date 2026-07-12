# Chapter 15: Community, Loyalty & Live Commerce

**Document ID:** SCP-COM-001-15  
**Version:** 1.0.0  
**Status:** ✅ Active  
**Traceability:** FR-COM-020–FR-COM-035, PRD-006, NFR-083  

---

## Purpose

Commerce-domain specification for **community engagement**, **loyalty programs**, **gamification rules**, and **live shopping** data — powering Marketing and Community experience layers on the storefront.

---

## 1. Scope

| In scope | Out of scope |
|----------|--------------|
| Loyalty points, tiers, referrals | PSP ledger settlement |
| Product Q&A, reviews, votes | Full social network |
| Wishlist, gift lists, shared collections | External forum hosting |
| Live session products pins | Video CDN encoding detail (Vol 10) |
| Gamification rule engine | Gambling regulation legal review (Vol 20) |

---

## 2. Loyalty Program Model

```sql
-- Conceptual entities
loyalty_programs (tenant_id, name, points_per_currency_unit, expiry_days)
loyalty_accounts (customer_id, points_balance, tier_id)
loyalty_tiers (id, name, threshold_points, benefits_json)
loyalty_transactions (account_id, type, points, order_id, created_at)
```

### 2.1 Earning Rules

| Event | Default points (NGN) |
|-------|----------------------|
| Order paid | 1 point per ₦100 spent |
| Review with photo | 50 bonus |
| Referral first order | 500 referrer / 200 referee |
| Birthday | 100 (once/year) |

Merchants configure rates; storefront displays **live balance** via Storefront API.

### 2.2 Tiers

Example: Bronze → Silver → Gold → VIP

Benefits: early access collections, free shipping threshold reduction, exclusive discounts (linked to Volume 5 Ch. 11 promotions).

---

## 3. Community Features

### 3.1 Product Q&A

- Customers ask; merchants or verified buyers answer
- Moderation queue in admin
- Upvote helpful answers
- RAG index for AI assistant (Volume 9)

### 3.2 Reviews

| Type | Moderation |
|------|------------|
| Text + star | Auto-publish if trusted; else queue |
| Photo/video | Manual review |
| Verified purchase | Badge from order linkage |

### 3.3 Social Graph (Phase 3)

| Feature | Entity |
|---------|--------|
| Follow brand | `customer_follows` |
| Save public collection | `shared_collections` |
| Share wishlist | Signed URL, optional password |
| Gift registry | `gift_lists` with SKU targets |
| Product vote | `product_votes` for “request again” |

All tenant-scoped RLS.

---

## 4. Gamification Engine

**Opt-in per store.** Default off.

| Mechanic | Rule |
|----------|------|
| Spin-to-win | Max 1 spin/customer/day; prizes = points or coupon |
| Scratch card | Post-purchase eligible |
| Daily check-in | Streak counter |
| Referral milestones | Badge at 3, 10, 25 referrals |
| Challenge | “Buy 3 orders this month” |

| Constraint | Detail |
|------------|--------|
| GAM-01 | No real-money gambling |
| GAM-02 | Cannot block checkout |
| GAM-03 | Probability disclosed in merchant settings |
| GAM-04 | Under-18 excluded if merchant sells age-restricted |

---

## 5. Live Commerce

### 5.1 Live Session Model

```sql
live_sessions (
  id, tenant_id, title, status, stream_url,
  scheduled_at, started_at, ended_at, host_user_id
)
live_session_products (
  session_id, product_id, pinned_at, highlight_order
)
live_session_messages (
  session_id, customer_id, body, created_at
)
```

### 5.2 Shopper UX

- Live badge on homepage when active
- Product pins with “Buy now” without leaving stream (Phase 4)
- Live chat moderated
- Auction mode optional (Phase 4) — bid holds via authorized payment

### 5.3 Integrations

- Stream: Mux / Cloudflare Stream (Volume 10)
- Inventory: real-time stock check on pin
- Orders: standard checkout; `live_session_id` attribution

---

## 6. Dynamic Pricing (Commerce Rules)

Storefront displays prices computed server-side:

| Rule type | Engine location |
|-----------|-----------------|
| Customer group | `price_lists` + customer tags |
| Quantity breaks | `volume_pricing` |
| Time window | `promotions` |
| Member tier | loyalty tier → price list |
| Wholesale | B2B account flag |

**API:** `GET /storefront/products/{handle}` returns `price`, `compare_at`, `badges[]`, `reason_codes[]`.

Themes render badges only — no client-side price math.

---

## 7. Events

| Event | Subscribers |
|-------|-------------|
| `LoyaltyPointsEarned` | Email, portal refresh |
| `TierUpgraded` | Email, ASI segment |
| `ReviewSubmitted` | Moderation, AI index |
| `LiveSessionStarted` | Push, homepage banner |
| `GamificationRewardGranted` | Portal notification |

---

## 8. Nigeria Considerations

- Loyalty points have no cash value unless merchant configures conversion (legal copy in Terms)
- Referral via WhatsApp share deep links
- Live shopping peak hours: evening WAT; CDN capacity plan
- NDPA: community UGC is personal data; deletion with account

---

## 9. Acceptance Criteria

- [ ] Loyalty balance visible on account and optionally header
- [ ] Tier benefits apply to price API responses
- [ ] Q&A and reviews tenant-isolated
- [ ] Gamification off by default; enabling requires merchant acknowledgment
- [ ] Live session pins reflect real-time stock
- [ ] Dynamic pricing never computed in theme JavaScript
- [ ] Gift list and wishlist share links expire configurable

---

## References

- [Volume 5 Ch. 11 — Promotions](./11-promotions-discounts-coupons.md)
- [Volume 4 Ch. 14 — Customer Portal UX](../04-design-system/14-intelligent-homepage-customer-portal.md)
- [Volume 15 — Live Shopping Roadmap](../15-future-roadmap/01-roadmap-overview.md)
- [Volume 9 Ch. 13 — AI Storefront](../09-ai-platform/13-ai-storefront-commerce.md)
