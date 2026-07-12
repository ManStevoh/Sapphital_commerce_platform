# Commerce Cart

**Package:** `commerce/cart`  
**Version:** 0.1.0  
**Layer:** Business Product (Layer 3)  
**Traceability:** ADR-023, Platform OS Ch. 13

## Purpose

Shopping cart primitives for the SAPPHITAL Commerce engine. Guest carts are keyed by `X-Session-ID`; all amounts are NGN kobo integers.

## Sprint 0 Scope

- `carts` and `cart_items` migrations
- Health endpoint: `GET /api/v1/commerce/cart/health`
- Get or create cart: `GET /api/v1/commerce/cart` (requires `tenant.context`, `X-Session-ID`)
- Add item: `POST /api/v1/commerce/cart/items` with `{ product_id, quantity }`

## References

- [Platform OS Ch. 13 §12](../../../docs/03-architecture/13-platform-os-architecture.md)
- [Vol 5 Ch. 01–04](../../../docs/05-commerce-engine/)
