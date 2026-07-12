# Legacy Platform → SCP Capability Matrix

**Document ID:** SCP-META-LEG-001  
**Version:** 1.3.0  
**Status:** ✅ Active  
**Source:** `marketplace/core/` (legacy codebase)  
**Target:** V2.0 SCP specification

---

## Purpose

Record which legacy capabilities are **documented in SCP**, **intentionally omitted**, or **superseded** — so implementation does not rediscover gaps.

---

## Platform Admin (Landlord → Platform Admin)

| Legacy | SCP | Doc |
|--------|-----|-----|
| Tenant CRUD, migrate, failed tenants | ✅ | Vol 16 Ch. 11 |
| Price plans, themes per plan | ✅ | Vol 16 Ch. 03, 11 |
| Package orders / payment logs | ✅ | Vol 16 Ch. 11 §6 |
| Landlord CMS (pages, testimonials, brands) | ✅ | Vol 16 Ch. 12 |
| Signup + plan checkout | ✅ | Vol 16 Ch. 12 |
| Subscription coupons | ✅ | Vol 16 Ch. 13 |
| Custom domain admin queue | ✅ | Vol 16 Ch. 07, 11 |
| Support tickets (landlord) | ✅ | Vol 16 Ch. 11 §9 |
| Impersonation | ✅ | ADR-010, Vol 16 Ch. 11 §8 |
| Commission / wallet (landlord) | ✅ | Vol 8 + Vol 16 Ch. 11 §12 |
| Module on/off | ✅ | ADR-023 Module Manager |
| **Domain reseller (GoDaddy)** | ❌ By design | Vol 16 Ch. 11 |
| **cPanel automation** | ❌ By design | ADR-022 |
| **Buyer wallet for plans** | ❌ By design | FSL direct checkout |
| **Token URL login** | ❌ By design | ADR-010 |
| **Legacy license/update wizard** | ❌ By design | CI/CD + Module Manager |

---

## Merchant / Commerce

| Legacy | SCP | Doc |
|--------|-----|-----|
| Products, variants, inventory | ✅ | Vol 5 Ch. 01–04 |
| Digital products | ✅ | Vol 5 Ch. 14 (unified catalog) |
| Campaigns + campaign products | ✅ | Vol 5 Ch. 20 |
| Coupons / promotions | ✅ | Vol 5 Ch. 11 |
| Compare products | ✅ | Vol 5 Ch. 21 |
| Product clone | ✅ | Vol 5 Ch. 21 |
| Product badges | ✅ | Vol 5 Ch. 21 |
| Units of measure | ✅ | Vol 5 Ch. 21 |
| CSV import/export | ✅ | Playbooks + Ch. 21 |
| Refund + chat | ✅ | Vol 5 Ch. 12 |
| Wishlist, loyalty, community | ✅ | Vol 5 Ch. 15 |
| WooCommerce import | ✅ | Vol 19 Ch. 12 |
| Integrations (GTM, Pixel, chat) | ✅ | Vol 19 Ch. 11 |
| Language string editor | ✅ | Vol 16 Ch. 14 |
| Site analytics | ✅ | Vol 14 Ch. 12 |
| Forms | ✅ | Vol 7 Ch. 11 |
| POS hold orders | ✅ | Vol 18 Ch. 12 |
| Mobile app sliders/intros | ✅ | Vol 18 Ch. 11 |
| ShipRocket | ✅ | Vol 5 Ch. 10 |
| 20+ global PSPs | ❌ By design | **Stripe + PayPal only** for international; Africa-first FSL (Vol 5 Ch. 17) |
| Separate DigitalProduct module | ⚠️ Unified | Single catalog types |
| **Service listings module** | 📋 Phase 3 | Vol 5 Ch. 22 — Bookings extension |

---

## Architecture Differences (Always)

| Legacy | SCP |
|--------|-----|
| MySQL per tenant | PostgreSQL + RLS |
| nwidart in `Modules/` | Platform OS packages |
| Blade themes | Next.js + Theme Engine |
| static_option KV | Typed config + Secrets |

---

## Intentional Omissions & Phase 3 Deferrals

Decisions below are **not P1/P2 gaps** — they are deliberate product choices. Do not implement as legacy parity without an ADR.

### Buyer wallet for SaaS billing — ❌ Omitted (by design)

| Aspect | Legacy platform | SCP |
|--------|-----------------|-----|
| What it was | Prospective merchant **deposits balance** into a buyer wallet on the marketing/landlord site, then pays for subscription packages from wallet balance | — |
| SCP approach | **FSL redirect checkout** (Paystack/Flutterwave/Stripe/PayPal) at signup and renewal | Vol 16 Ch. 04, Ch. 12 |
| Why omitted | Standard SaaS billing pattern; avoids stored-value compliance, reconciliation complexity, and abuse (wallet top-up fraud); aligns with ADR-004 redirect model | |
| What we keep | Platform subscription invoices, dunning, refunds via FSL — not a prepaid customer wallet | Vol 16 Ch. 04, Ch. 11 §6 |
| Merchant wallets | **Vendor/marketplace** wallets and payouts remain in scope (Vol 8, FSL Ch. 16) — different domain from SaaS buyer wallet | Vol 8 |

**Implementation rule:** No `buyer_wallet`, `wallet_deposit`, or `pay_plan_from_balance` on `apps/marketing/` or Platform Billing.

### Service listings module — 📋 Phase 3 vertical (not P1/P2)

| Aspect | Legacy platform | SCP |
|--------|-----------------|-----|
| What it was | Separate **`Modules/Service`** catalog — service categories, listing pages, brochure-style services **not** in the product/cart flow | — |
| Phase 1–2 | — | Sellable **`product.type = service`** in unified catalog (Vol 5 Ch. 01); basic `ServiceDefinition` metadata (Vol 5 Ch. 14) |
| Phase 3 | — | **`Modules/Extensions/Bookings/`** — appointments, availability, booking checkout; theme sections (`services-grid`, `booking-cta`) in Vol 6 | Vol 5 Ch. 22 |
| Why deferred | Legacy module duplicates catalog; SCP consolidates on one product model + optional booking extension for verticals (salons, clinics, agencies) | ADR-023 |
| Not a clone | Do **not** port separate Service entity + parallel admin; extend Commerce catalog + Bookings extension | |

**Implementation rule:** Phase 1 launch does not require a standalone Service listings module. CMS pages + service product type cover brochure sites; full booking is Phase 3 gate.

---

## References

- [Engineering Knowledge Base](./engineering-knowledge-base.md)
- [Platform Admin Ch. 11](../16-saas-multi-tenancy/11-platform-admin-operator-guide.md)
- [Bookings & Service Commerce Ch. 22](../05-commerce-engine/22-bookings-and-service-commerce.md)
