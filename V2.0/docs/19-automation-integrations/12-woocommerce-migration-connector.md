# Chapter 12: WooCommerce Migration Connector

**Document ID:** SCP-AUT-001-12  
**Version:** 1.0.0  
**Status:** ✅ Active  
**Traceability:** Vol 16 Ch. 09, PRD-002, ADR-023  
**Legacy mapping:** `Modules/WooCommerce`

---

## Purpose

Technical specification for **`Connectors/WooCommerce/`** — import products, categories, customers, and orders from WooCommerce REST API into `Modules/Commerce/`.

## Scope

- OAuth/API key connection
- Selective import (products only, + customers, + orders)
- Field mapping and idempotency
- Background job chunked import
- Onboarding wizard integration (Phase 2)

## Out of Scope

- Ongoing two-way sync (Phase 3+)
- WordPress plugin hosting

---

## 1. Connection

| Field | Description |
|-------|-------------|
| `store_url` | https://merchant.com |
| `consumer_key` | WooCommerce REST |
| `consumer_secret` | Encrypted in Secrets |

`POST /api/v1/admin/integrations/woocommerce/connect` — test call `GET /wp-json/wc/v3/system_status`.

---

## 2. Import Modes

| Mode | Data |
|------|------|
| **Catalog** | Products, variants, categories, images |
| **Customers** | Billing/shipping addresses |
| **Orders** | Historical read-only (optional) |

Each mode: async job with progress in admin UI.

---

## 3. Mapping

| WooCommerce | SCP |
|-------------|-----|
| `simple product` | Product + default variant |
| `variable product` | Product + variants |
| `category` | Collection (manual) |
| `sku` | Variant SKU |
| `regular_price` | `price_cents` NGN or converted |
| Images | Media engine download → CDN |

**Idempotency:** `external_id` = `woocommerce:{id}` on products.

---

## 4. Jobs

```text
WooCommerceImportJob
  → fetch page of products
  → map → upsert
  → emit ProductCreated/Updated
  → continue cursor
```

Rate limit: respect WooCommerce 429; exponential backoff.

---

## 5. Errors

| Error | Handling |
|-------|----------|
| Auth failure | Disable connector; notify merchant |
| Image download fail | Product created; placeholder image |
| Duplicate SKU | Skip or merge policy (merchant choice) |

Report: CSV of failures downloadable.

---

## 6. Acceptance Criteria

- [ ] 500 products import without timeout (chunked)
- [ ] Re-run import updates existing by external_id
- [ ] Categories appear as collections
- [ ] Progress UI shows % complete
- [ ] NDPA: customer import requires merchant confirmation checkbox

---

## References

- [Vol 16 Ch. 09 — Onboarding import paths](../16-saas-multi-tenancy/09-ai-guided-merchant-onboarding.md)
- [Module Template](../00-meta/module-template.md)
