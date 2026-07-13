# Commerce Catalog

**Package:** `commerce/catalog`  
**Version:** 0.1.0  
**Layer:** Business Product (Layer 3)  
**Traceability:** ADR-023, Platform OS Ch. 13

## Purpose

Product catalog, variants, categories, and inventory primitives for the SAPPHITAL Commerce engine.

## Sprint 0 Scope

- Product model and `products` migration (uuid, tenant_id, name, slug, price_kobo, status, inventory_qty)
- Health endpoint: `GET /api/v1/commerce/catalog/health`
- Tenant-scoped list: `GET /api/v1/commerce/catalog/products` (requires `tenant.context` middleware)

## Phase 1 — Product CRUD (P1.7)

All product endpoints require `tenant.context` middleware (`X-Tenant-ID` header).

| Method | Path | Description |
|--------|------|-------------|
| `GET` | `/api/v1/commerce/catalog/products` | List products for current tenant |
| `POST` | `/api/v1/commerce/catalog/products` | Create product (entitlement-checked) |
| `GET` | `/api/v1/commerce/catalog/products/{id}` | Show single product (tenant-scoped; 404 if not owned) |
| `PUT` | `/api/v1/commerce/catalog/products/{id}` | Update `name`, `slug`, `price_kobo`, `status`, `inventory_qty` |
| `DELETE` | `/api/v1/commerce/catalog/products/{id}` | Hard-delete product (tenant-scoped; 404 if not owned) |

## Phase 2 — Collections (P2.2)

| Method | Path | Description |
|--------|------|-------------|
| `GET` | `/api/v1/commerce/catalog/collections` | List collections (tenant) |
| `GET` | `/api/v1/commerce/catalog/collections/published` | Live published collections |
| `GET` | `/api/v1/commerce/catalog/collections/by-slug/{slug}` | Storefront resolve + products |
| `GET` | `/api/v1/commerce/catalog/collections/{id}` | Show collection |
| `GET` | `/api/v1/commerce/catalog/collections/{id}/products` | Resolved product list |
| `POST` | `/api/v1/commerce/catalog/collections` | Create manual or smart (`catalog.write`) |
| `PUT` | `/api/v1/commerce/catalog/collections/{id}` | Update collection |
| `PUT` | `/api/v1/commerce/catalog/collections/{id}/products` | Sync manual membership |
| `DELETE` | `/api/v1/commerce/catalog/collections/{id}` | Delete collection |

Smart presets: `new_arrivals`, `on_sale`, `best_sellers`. Rule fields allowlisted (`tag`, `type`/`fulfillment_type`, `price_kobo`/`price_cents`, `created_at`). Scheduler: `catalog:process-scheduled-collections` every minute.

## References

- [Platform OS Ch. 13 §12](../../../docs/03-architecture/13-platform-os-architecture.md)
- [Vol 5 Ch. 01–04](../../../docs/05-commerce-engine/)
- [Vol 5 Ch. 03 Collections](../../../docs/05-commerce-engine/03-collections-and-categories.md)
