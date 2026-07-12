# @sapphital/scp-marketing

Public marketing site and signup funnel for SAPPHITAL (`sapphital.africa`).

**Spec:** [Vol 16 Ch. 12 — Platform Marketing Site & Signup](../../docs/16-saas-multi-tenancy/12-platform-marketing-site-and-signup.md) · [Platform OS Ch. 13](../../docs/03-architecture/13-platform-os-architecture.md)

## Nigeria-first defaults

Phase 1 targets Nigeria as the primary market:

- Default currency: **NGN** in pricing and plan copy
- Primary payment provider: **Paystack** for merchant subscription checkout
- Public site is tenant-agnostic; no merchant data is exposed

## Local development

```bash
cd apps/marketing
npm install
npm run dev
```

Open [http://localhost:3003](http://localhost:3003). The API shell must be running separately:

```bash
cd ../..   # repo root (V2.0/scp)
php artisan serve   # http://localhost:8000
```

Verify API connectivity: [http://localhost:8000/api/health](http://localhost:8000/api/health)

## Architecture notes

- No tenant context required — public marketing surface.
- Communicates with the platform via documented HTTP APIs only; no direct Eloquent or cross-package imports.
- Sprint 0 delivers a placeholder homepage only; signup funnel arrives in Phase 1 step 1.5.

## Scripts

| Command | Purpose |
|---------|---------|
| `npm run dev` | Dev server on port 3003 |
| `npm run build` | Production build |
| `npm run start` | Production server |
| `npm run typecheck` | TypeScript check |
