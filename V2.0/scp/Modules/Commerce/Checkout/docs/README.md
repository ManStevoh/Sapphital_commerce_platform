# Commerce Checkout

**Package:** `commerce/checkout`  
**Version:** 0.1.0  
**Layer:** Business Product (Layer 3)  
**Traceability:** ADR-023, Platform OS Ch. 13

## Purpose

Checkout session primitives for the SAPPHITAL Commerce engine. Creates pending sessions from carts with NGN kobo totals. Gift cards: preset denominations (₦5k / ₦10k / ₦25k), unique codes, partial checkout redemption, and `checkout:expire-gift-cards` hourly schedule.

## Gift cards

- Merchant: `POST /api/v1/commerce/gift-cards`, `GET …/by-code/{code}`, `POST …/{id}/disable`
- Storefront: `POST /api/v1/commerce/checkout/sessions/{id}/gift-card` with `{ code }`
- Redemption finalizes when an order is created from the checkout session

## Sprint 0 Scope

- `checkout_sessions` migration (uuid, tenant_id, cart_id, status, total_kobo, paystack_reference)
- Health endpoint: `GET /api/v1/commerce/checkout/health`
- Create session: `POST /api/v1/commerce/checkout/sessions` with `{ cart_id }` (requires `tenant.context`)

## References

- [Platform OS Ch. 13 §12](../../../docs/03-architecture/13-platform-os-architecture.md)
- [Vol 5 Ch. 01–04](../../../docs/05-commerce-engine/)
