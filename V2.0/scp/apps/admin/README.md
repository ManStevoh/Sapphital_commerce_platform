# @sapphital/scp-admin

Merchant admin console for the SAPPHITAL Commerce Platform. Catalog, orders, settings, and store management.

**Spec:** [Vol 4 Ch. 07 — Admin & Merchant Dashboard UX](../../docs/04-design-system/07-admin-and-merchant-dashboard-ux.md) · [Platform OS Ch. 13](../../docs/03-architecture/13-platform-os-architecture.md) · **P1.12**

## Nigeria-first defaults

Phase 1 targets Nigeria as the primary market:

- Default currency: **NGN**
- Primary payment provider: **Paystack** (configured via platform admin; merchants never handle raw card data)
- NDPA compliance requirements apply to all merchant-facing data surfaces

## Local development

```bash
cd apps/admin
cp .env.example .env.local
npm install
npm run dev
```

Open [http://localhost:3001](http://localhost:3001). The API shell must be running separately:

```bash
cd ../..   # repo root (V2.0/scp)
php artisan serve   # http://localhost:8000
```

Sign in with a merchant account created via signup (`/api/v1/signup`) or seeded test data.

## Features (P1.12)

| Surface | Route | API |
|---------|-------|-----|
| Login | `/login` | `POST /api/v1/auth/merchant/login` |
| Product list | `/products` | `GET /api/v1/commerce/catalog/products` |
| Create product | `/products/new` | `POST /api/v1/commerce/catalog/products` |
| Edit product | `/products/{id}/edit` | `PUT /api/v1/commerce/catalog/products/{id}` |
| Delete product | action on list | `DELETE /api/v1/commerce/catalog/products/{id}` |

Auth token and `tenant_id` (from `/api/v1/auth/me`) are stored in `localStorage`. Protected routes redirect to `/login` when unauthenticated.

## Scripts

| Command | Purpose |
|---------|---------|
| `npm run dev` | Dev server on port 3001 |
| `npm run build` | Production build |
| `npm run start` | Production server |
| `npm run typecheck` | TypeScript check |
