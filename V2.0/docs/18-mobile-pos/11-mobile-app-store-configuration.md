# Chapter 11: Mobile App Store Configuration

**Document ID:** SCP-MOB-001-11  
**Version:** 1.0.0  
**Status:** ✅ Active  
**Traceability:** Vol 18 Ch. 03, Vol 12 Storefront API  
**Legacy mapping:** `Modules/MobileApp` admin (sliders, intros, featured)

---

## Purpose

Merchant admin specification for **native mobile app presentation** — content fed to `apps/mobile-customer/` via Storefront/Mobile API.

## Scope

- Home sliders / banners
- Intro/onboarding screens (first launch)
- Featured products and collections
- Campaign highlights for mobile home

## Out of Scope

- App binary build pipeline (Vol 15)

---

## 1. Entities

| Entity | Fields |
|--------|--------|
| **MobileSlider** | `image_id`, `title`, `link_type`, `link_id`, `position`, `active` |
| **MobileIntro** | `title`, `body`, `image_id`, `position` (first-run carousel) |
| **MobileFeaturedProduct** | `product_id`, `position` |
| **MobileFeaturedCollection** | `collection_id`, `position` |

---

## 2. Admin UI

**Sales channels → Mobile app → Appearance**

- Drag-drop slider ordering
- Preview on device frame
- Schedule slider (optional Phase 2)

---

## 3. API

`GET /api/v1/mobile/home` — returns sliders, featured, active campaign summary.

Cached per store; invalidate on publish.

---

## 4. Acceptance Criteria

- [ ] Slider changes reflect in app within 60s
- [ ] Intro screens show once per app install
- [ ] Featured products respect stock status
- [ ] Broken image URLs fall back to placeholder

---

## References

- [Ch. 03 — Customer Shopping App](./03-customer-shopping-app.md)
