# Chapter 13: Platform Plan Coupons & Trial Programs

**Document ID:** SCP-SAAS-001-13  
**Version:** 1.0.0  
**Status:** ✅ Active  
**Traceability:** Vol 16 Ch. 03–04, PRD-003  
**Legacy mapping:** Landlord subscription coupons + zero-price trial history

---

## Purpose

Define **coupons for platform subscriptions** (merchant pays Sapphital) — distinct from merchant product coupons (Volume 5 Ch. 11).

## Scope

- Platform coupon codes (`LAUNCH50`, `PARTNER100`)
- Percentage/fixed discount on plan invoices
- Free trial extensions
- Partner/agency redemption tracking
- Zero-amount checkout for 100% coupons

## Out of Scope

- Product/store coupons (Vol 5)
- Marketplace vendor fees (Vol 8)

---

## 1. Entities

| Entity | Fields |
|--------|--------|
| **PlatformCoupon** | `code`, `discount_type`, `value`, `plan_ids[]`, `max_redemptions`, `used_count`, `starts_at`, `ends_at`, `status` |
| **PlatformCouponRedemption** | `coupon_id`, `organization_id`, `subscription_id`, `discount_cents`, `redeemed_at` |

---

## 2. Rules

| Rule | Description |
|------|-------------|
| BR-PC-001 | One platform coupon per initial subscription checkout |
| BR-PC-002 | 100% discount skips FSL redirect; activates trial/paid per plan |
| BR-PC-003 | Coupon applies to first invoice only unless `recurring=true` (enterprise deals) |
| BR-PC-004 | Expired coupon returns 422 with clear message on marketing checkout |
| BR-PC-005 | Redemption audit in Platform Audit log |

---

## 3. Trial Programs

| Program | Configuration |
|---------|---------------|
| Standard trial | `trial_days` on plan (default 14) |
| Extended trial coupon | Adds days to trial_end |
| No-card trial | Plan flag `trial_requires_payment_method=false` (abuse-monitored) |
| Trial → paid | Dunning email sequence (Ch. 04) |

**Zero-price plan history:** logged as `subscription.trial_started` / `platform_coupon.applied` — not a separate wallet.

---

## 4. Admin UI (Platform Admin)

- CRUD coupons
- Redemption report
- Disable abusive codes

---

## 5. API

`POST /api/v1/platform/checkout/validate-coupon` — public, rate-limited.

---

## 6. Acceptance Criteria

- [ ] 50% coupon reduces first invoice correctly
- [ ] 100% coupon activates subscription without PSP redirect
- [ ] Product coupons cannot be used on platform checkout
- [ ] Redemptions capped per `max_redemptions`

---

## References

- [Ch. 12 — Marketing Signup](./12-platform-marketing-site-and-signup.md)
- [Vol 5 Ch. 11 — Merchant promotions](../05-commerce-engine/11-promotions-discounts-coupons.md)
