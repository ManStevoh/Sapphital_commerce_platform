# @sapphital/scp-storefront

Tenant storefront runtime for the SAPPHITAL Commerce Platform. Renders merchant catalogs via Next.js 15 App Router with SSR.

**Spec:** [Vol 6 — Theme Engine](../../docs/06-theme-engine/README.md) · [ADR-017](../../docs/00-meta/adr/017-three-system-storefront-architecture.md) · [Platform OS Ch. 13](../../docs/03-architecture/13-platform-os-architecture.md) · **P1.9**

## Nigeria-first defaults

Phase 1 targets Nigeria as the primary market:

- Default currency: **NGN**
- Default theme: **scp-dawn** (Lagos Atelier) — `#1B4332` primary green
- Primary payment provider: **Paystack** (via FSL — no direct connector imports in this app)
- Region: `af-ng-lagos` per [ADR-011](../../docs/00-meta/adr/011-data-residency-africa.md)

## Local development

```bash
cd apps/storefront
cp .env.example .env.local
npm install
npm run dev
```

Open [http://localhost:3000](http://localhost:3000). For tenant subdomain resolution locally, add to your hosts file:

```
127.0.0.1 acme-store.shops.sapphital.test
```

Then visit [http://acme-store.shops.sapphital.test:3000](http://acme-store.shops.sapphital.test:3000).

Without a subdomain, set `NEXT_PUBLIC_DEFAULT_TENANT_SLUG` or `NEXT_PUBLIC_TENANT_ID` in `.env.local`.

The API shell must be running separately:

```bash
cd ../..   # repo root (V2.0/scp)
php artisan serve   # http://localhost:8000
```

## Features (P1.9)

| Surface | Route | API |
|---------|-------|-----|
| Product grid | `/` | `GET /api/v1/commerce/catalog/products` |
| Product detail | `/products/{id}` | `GET /api/v1/commerce/catalog/products/{id}` |
| Add to cart | button on detail | `POST /api/v1/commerce/cart/items` |
| Cart | `/cart` | `GET /api/v1/commerce/cart` |
| Checkout | `/checkout` | `POST /api/v1/commerce/checkout/sessions` → `POST /api/v1/platform/financial-services/payments/initialize` |
| Theme config | (SSR hook) | `GET /api/v1/commerce/storefront/theme` |
| Shipping rates | checkout/cart | `GET /api/v1/commerce/shipping/rates?order_total_kobo={n}` |

### Checkout flow

1. Cart page loads session cart via `X-Session-ID` + `X-Tenant-ID`
2. Checkout creates a checkout session from `cart_id`
3. Payment initialize returns Paystack `authorization_url` and `reference`
4. Shopper completes payment on Paystack hosted page

Tenant resolution:

1. Subdomain `*.shops.sapphital.test` → slug via middleware `x-tenant-slug` header
2. Slug → tenant ID via `GET /api/v1/platform/tenancy/tenants/by-slug/{slug}`
3. Dev fallback: `NEXT_PUBLIC_DEFAULT_TENANT_SLUG` or `NEXT_PUBLIC_TENANT_ID`

Session cart uses `X-Session-ID` persisted in `localStorage` (`lib/session.ts`).

## API client (`lib/api.ts`)

| Function | Purpose |
|----------|---------|
| `getCart` | Fetch or create cart for session |
| `createCheckout` | Create checkout session from cart |
| `initializePayment` | Initialize Paystack payment |
| `getShippingRates` | Applicable shipping rates for order total |
| `fetchTheme` | Active theme manifest + merchant settings |

## Scripts

| Command | Purpose |
|---------|---------|
| `npm run dev` | Dev server on port 3000 |
| `npm run build` | Production build |
| `npm run start` | Production server |
| `npm run typecheck` | TypeScript check |
