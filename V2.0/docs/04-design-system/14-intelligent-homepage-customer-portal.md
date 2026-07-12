# Chapter 14: Intelligent Homepage, Customer Portal & Fintech Checkout

**Document ID:** SCP-DS-001-14  
**Version:** 1.0.0  
**Status:** ✅ Active  
**Traceability:** ADR-017, ADR-018, ADR-004, NFR-001, NFR-012  

---

## Purpose

UX specification for **segment-aware homepages**, **customer portal** (not an afterthought), and **fintech-grade checkout** — the Commerce and Intelligence layers as shoppers experience them.

---

## 1. Intelligent Homepage

### 1.1 Segment Recipes

Runtime selects one recipe per request (Volume 6 Ch. 12):

| Segment | Modules (typical) |
|---------|-------------------|
| First-time | Hero → Best sellers → Trending → Testimonials → Categories |
| Returning | Continue shopping → Recently viewed → Recommended → Wishlist → Flash sale |
| VIP | Exclusive offers → Points → Recommended → VIP collection → Early access |
| Cart abandoner | Cart reminder → Free shipping threshold → Best sellers |

**Merchant does not maintain three pages** — one template, dynamic `order` + `data_source` per section.

### 1.2 Visual Rules

- Segment modules use same section components (no duplicate UI code)
- “Recommended for you” shows reason label
- Cold start never shows empty personalized blocks
- VIP content requires loyalty tier from Commerce API

---

## 2. Storytelling PDP Sequence

Immersive product narrative (not SKU-only):

```text
Lifestyle hero media
↓
Headline + value proposition
↓
Short video (tap to play)
↓
Feature highlights (icons)
↓
Testimonials / UGC strip
↓
Specifications table
↓
Reviews + Q&A
↓
Accessories / FBT
↓
Sticky purchase panel
```

Merchants enable/disable blocks via section settings; order configurable in Visual Builder.

---

## 3. Interactive Sections (Merchant-Enabled)

| Section | Interaction | Perf gate |
|---------|-------------|-----------|
| Countdown | Real promotion end time | No fake timers |
| Before/after slider | Drag handle | Lazy load images |
| Product hotspots | Click pins on image | ≤ 20 KB JS |
| Lookbook carousel | Swipe | Reduced motion fallback |
| 3D viewer | Optional glTF | Phase 4; lazy |
| Video story | Poster + play | No autoplay sound |

All gated by Theme Engine `performanceBudget` (Volume 6 Ch. 11).

---

## 4. Social Commerce Surfaces

| Element | Source | UX |
|---------|--------|-----|
| Instagram gallery | Cached API/media | Grid, links to posts |
| TikTok clips | Cached thumbnails | Tap opens external or embed lite |
| Customer photos | Review uploads | Moderation queue |
| Video reviews | Review attachment | Inline player on PDP |
| UGC carousel | `social-gallery` section | Above reviews |

No heavy third-party embed SDKs on initial load.

---

## 5. Customer Portal

**Route:** `/account` (storefront authenticated area)

### 5.1 Navigation (Mobile-First)

```text
Overview
Orders
Returns
Wishlist
Rewards
Subscriptions
Addresses
Payment methods (tokenized via PSP)
Invoices / receipts
Support
AI assistant
Recommendations
```

### 5.2 Overview Dashboard

```text
Welcome back, {name}
Order in transit: #SCP-9281 — [Track]
Reward balance: 1,240 points
Continue shopping: [product cards]
Ask AI: [quick prompt]
```

### 5.3 Design

- Same SDS tokens as storefront; calmer density than admin
- Cards not tables on mobile
- Status badges from `OrderStatusBadge`
- Download invoice PDF
- NDPA: export data, delete account, consent toggles

---

## 6. Fintech-Grade Checkout

Closer to Paystack/Revolut than legacy multi-page forms.

### 6.1 Layout (Mobile One-Page)

```text
┌─────────────────────────────┐
│ Checkout          Step 1 of 1│
├─────────────────────────────┤
│ Express: Pay with saved ▣   │
├─────────────────────────────┤
│ Contact (phone primary)     │
│ Delivery (autocomplete)     │
│ Shipping method             │
│ Payment method              │
├─────────────────────────────┤
│ Order summary (collapsible) │
│ Pay ₦26,500.00   [sticky]   │
│ 🔒 Secure · Paystack        │
└─────────────────────────────┘
```

### 6.2 Features

| Feature | Phase |
|---------|-------|
| Guest checkout | 1 |
| One-page mobile | 1 |
| Address autocomplete (Google/Local) | 1.1 |
| Saved addresses | 1.1 |
| Express checkout (returning) | 1.1 |
| Saved payment via PSP token | 2 |
| Progress indicator | 1 |
| Minimal fields | 1 |
| Phone +234 validation | 1 |

**Locked template** — merchants cannot remove trust row or payment security copy (ADR-004).

---

## 7. Dynamic Pricing Display

Storefront **displays** rules from Commerce; never computes in theme JS.

| Rule type | UI treatment |
|-----------|--------------|
| Sale | Strike-through compare-at |
| Member/VIP | “Member price” badge |
| Wholesale tier | “Trade price” after login |
| Quantity break | “Buy 3+ save 10%” under price |
| Flash sale | Countdown tied to promotion entity |
| Student discount | Verified badge after ID check (Phase 3) |

---

## 8. Loyalty & Gamification (Storefront UX)

| Element | Display |
|---------|---------|
| Points balance | Header chip when logged in |
| Tier | “Gold member” on account |
| Progress bar | “₦12,000 to Silver” |
| Referral | Share link + reward status |
| Gamification | Opt-in modals only; never block checkout |
| Spin/scratch | Phase 3; frequency capped |

Gamification sections disabled by default; merchant enables per store.

---

## 9. Localization UX

| Element | Behavior |
|---------|----------|
| Language | Subpath or domain; hreflang (ADR-015) |
| Currency | Tenant default; multi-currency display Phase 3 |
| Tax | “VAT included” caption when applicable |
| Units | Metric default; merchant setting |
| RTL | Phase 3 architecture ready |
| Region content | Segment by geo within one store |

---

## 10. Acceptance Criteria

- [ ] Three homepage segment recipes render without separate templates
- [ ] Customer portal includes orders, returns, wishlist, rewards, support, AI entry
- [ ] Checkout completable one-page on mobile with ≤ 3 taps to PSP
- [ ] Dynamic pricing badges reflect Commerce API only
- [ ] Interactive sections pass perf budget when enabled
- [ ] Social galleries load without third-party SDK on first paint
- [ ] Portal NDPA export/delete reachable in ≤ 3 taps

---

## References

- [Chapter 08 — Storefront & Checkout UX](./08-storefront-and-checkout-ux.md)
- [Chapter 13 — Visual Direction](./13-storefront-visual-direction.md)
- [Volume 5 Ch. 15 — Community & Loyalty](../05-commerce-engine/15-community-loyalty-live-commerce.md)
- [Volume 6 Ch. 12 — Eight Layers](../06-theme-engine/12-storefront-engine-eight-layers.md)
