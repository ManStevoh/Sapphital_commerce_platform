# Chapter 21: Catalog Operations — Compare, Clone, Badges, Units & Export

**Document ID:** SCP-COM-005-21  
**Version:** 1.0.0  
**Status:** ✅ Active  
**Traceability:** FR-020, Vol 4 Ch. 13  
**Legacy mapping:** Product clone, compare, badges, units, CSV export

---

## Purpose

Merchant catalog **operations** not covered in core product CRUD (Ch. 01) — parity with proven legacy merchant workflows worth keeping.

---

## 1. Product Clone

**Action:** `CloneProductAction` duplicates product + variants + media references + collections links.

| Copied | Not copied |
|--------|------------|
| Title (suffix " (Copy)"), description, variants, prices | SKU (must regenerate) |
| Categories/collections | Reviews, sales stats |
| SEO fields | External IDs |

Event: `ProductCloned`. Permission: `commerce.products.create`.

---

## 2. Product Compare

**Customer feature:** compare up to **4** products side-by-side.

| Layer | Implementation |
|-------|----------------|
| Session/cookie | `compare_list` variant IDs |
| Route | `/compare` |
| API | `POST /api/v1/storefront/compare/add`, `DELETE .../remove` |
| UI | Spec table: price, attributes, rating (Vol 4 PDP compare + dedicated page) |

Persist compare list for logged-in customers in profile.

---

## 3. Product Badges

Manageable badges (not only theme CSS):

| Entity | Fields |
|--------|--------|
| **ProductBadge** | `name`, `slug`, `color`, `icon?` |
| **ProductBadgeAssignment** | `product_id`, `badge_id`, `starts_at?`, `ends_at?` |

System badges (auto): `sale`, `new` (30 days), `low_stock` — merchant badges: `bestseller`, `organic`.

Theme section renders max 2 badges (Vol 4 visual direction).

---

## 4. Units of Measure

| Entity | Usage |
|--------|--------|
| **Unit** | `name` (piece, kg, litre, carton), `abbreviation` |

Variant field: `unit_id` — displayed on PDP and B2B wholesale (Phase 2).

---

## 5. Bulk Export

| Export | Format | Fields |
|--------|--------|--------|
| Products | CSV, XLSX | title, sku, price, qty, categories |
| Orders | CSV | Vol 7 reporting integration |

Async job for > 1,000 rows. Permission: `commerce.products.export`.

Import documented in playbooks; export symmetric.

---

## 6. Trash & Restore

Soft delete (FR-025): products in trash 30 days — restore action in admin.

---

## 7. Acceptance Criteria

- [ ] Clone creates editable draft product
- [ ] Compare page shows 2–4 products with attributes
- [ ] Manual badge appears on storefront
- [ ] Export 5,000 products completes via queue
- [ ] Unit displayed on PDP when set

---

## References

- [Ch. 01 — Catalog](./01-catalog-and-products.md)
- [Ch. 02 — Variants](./02-variants-attributes-pricing.md)
