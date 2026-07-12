# @sapphital/scp-platform-admin

Landlord operator console for the SAPPHITAL Commerce Platform. Tenant lifecycle, billing, provisioning, and platform operations.

**Spec:** [Vol 16 Ch. 11 — Platform Admin Operator Guide](../../docs/16-saas-multi-tenancy/11-platform-admin-operator-guide.md) · [Platform OS Ch. 13](../../docs/03-architecture/13-platform-os-architecture.md)

## Nigeria-first defaults

Phase 1 targets Nigeria as the primary market:

- Default currency: **NGN**
- Primary payment provider: **Paystack** for SaaS billing and merchant payouts
- Platform admin routes use a separate guard; cannot inherit merchant tenant context

## Local development

```bash
cd apps/platform-admin
npm install
npm run dev
```

Open [http://localhost:3002](http://localhost:3002). The API shell must be running separately:

```bash
cd ../..   # repo root (V2.0/scp)
php artisan serve   # http://localhost:8000
```

Verify API connectivity: [http://localhost:8000/api/health](http://localhost:8000/api/health)

## Architecture notes

- Operator authentication is platform-scoped (landlord), not tenant-scoped.
- Communicates with the platform via documented HTTP APIs only; no direct Eloquent or cross-package imports.
- Sprint 0 delivers a placeholder homepage only; operator surfaces arrive in Phase 1 step 1.6.

## Scripts

| Command | Purpose |
|---------|---------|
| `npm run dev` | Dev server on port 3002 |
| `npm run build` | Production build |
| `npm run start` | Production server |
| `npm run typecheck` | TypeScript check |
