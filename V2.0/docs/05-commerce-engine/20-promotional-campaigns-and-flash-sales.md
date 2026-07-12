# Chapter 20: Promotional Campaigns & Flash Sales

**Document ID:** SCP-COM-005-20  
**Version:** 1.0.0  
**Status:** ✅ Active  
**Traceability:** Vol 5 Ch. 11, Vol 4 Ch. 14  
**Legacy mapping:** `Modules/Campaign`

---

## Purpose

Define **Campaign** as a merchandising aggregate — time-bound promotional landing experiences with assigned products, inventory tracking, and storefront routes — separate from coupon/promotion rules (Ch. 11).

## Scope

- Campaign CRUD and scheduling
- Campaign product assignments
- Campaign landing URL `/campaigns/{slug}`
- Sold quantity tracking
- Flash sale countdown sections (theme integration)

## Out of Scope

- Discount logic (links to Promotion in Ch. 11)

---

## 1. Entities

| Entity | Key fields |
|--------|------------|
| **Campaign** | `id`, `tenant_id`, `store_id`, `title`, `slug`, `description`, `hero_media_id`, `starts_at`, `ends_at`, `status`, `promotion_id?` |
| **CampaignProduct** | `campaign_id`, `product_id`, `variant_id?`, `position`, `featured` |
| **CampaignSoldProduct** | `campaign_id`, `order_item_id`, `quantity` (analytics) |

---

## 2. Business Rules

| Rule | Description |
|------|-------------|
| BR-CAM-001 | Campaign visible only when `status=active` and within date window |
| BR-CAM-002 | Optional linked promotion auto-applies at cart |
| BR-CAM-003 | Sold counts updated on `OrderPaid` event |
| BR-CAM-004 | Slug unique per store |

---

## 3. Storefront

- Route: `/campaigns/{slug}` — ISR, tenant-scoped
- Section: `campaign-banner`, `campaign-product-grid` (Vol 6 section catalog)
- Homepage block: active campaign carousel (Vol 4 Ch. 14)

---

## 4. Admin UI

**Marketing → Campaigns** — list, create, assign products (drag sort), preview, publish.

---

## 5. Events

- `CampaignStarted`, `CampaignEnded`, `CampaignProductSold`

---

## 6. Acceptance Criteria

- [ ] Campaign page lists assigned products only
- [ ] Countdown reflects real `ends_at` (store timezone)
- [ ] Linked promotion applies in checkout
- [ ] Sold report matches order lines

---

## References

- [Ch. 11 — Promotions](./11-promotions-discounts-coupons.md)
- [Vol 4 Ch. 14 — Intelligent homepage](../04-design-system/14-intelligent-homepage-customer-portal.md)
