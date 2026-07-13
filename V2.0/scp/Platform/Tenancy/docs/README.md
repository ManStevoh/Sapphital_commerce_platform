# Platform Tenancy

**Package:** `platform/tenancy`  
**Version:** 0.1.0  
**Layer:** Platform Kernel (Layer 1)  
**Traceability:** ADR-002, ADR-023, Platform OS Ch. 13

## Purpose

Tenant registry and isolation primitives for the SAPPHITAL Platform OS. Owns the `tenants` table and tenant context resolution consumed by all tenant-scoped packages.

## Sprint 0 Scope

- Package scaffold with health endpoint
- `tenants` migration and tenant isolation foundation (Phase 1.1)

## Row-Level Security (RLS)

Per **ADR-002** and **Vol 17 Ch. 03**, tenant data is isolated at the database layer using PostgreSQL RLS. The `tenants` table uses a self-referential policy: each session may only read or write the row whose `id` matches the session variable `app.current_tenant_id`.

### Session variable

| Variable | Set by | Purpose |
|----------|--------|---------|
| `app.current_tenant_id` | `SetTenantContext` middleware | Filters `tenants` rows to the resolved tenant |

When the variable is unset or empty, RLS fails closed — no tenant rows are visible.

### Middleware

Register `tenant.context` on tenant-scoped routes:

```php
Route::middleware(['api', 'tenant.context'])->group(function (): void {
    // tenant-scoped handlers
});
```

`Platform\Tenancy\Middleware\SetTenantContext` resolves tenant context in this order:

1. **`X-Tenant-ID` request header** — UUID of the tenant (API clients)
2. **Subdomain** — first label of the host (e.g. `acme.sapphital.com` → slug `acme` → tenant lookup)

On success, the middleware executes (PostgreSQL only):

```php
DB::statement('SET app.current_tenant_id = ?', [$tenantId]);
```

On SQLite (local package tests), the session variable is skipped; `tenant_id` is still attached to the request attributes.

### Application-level scoping

All manifest models use `Platform\Tenancy\Models\Concerns\BelongsToTenant`:

- When `TenantContext::id()` is set (via `X-Tenant-ID` / subdomain middleware), Eloquent queries auto-filter by `tenant_id`.
- When context is absent (console jobs, webhooks, platform ops), the scope is a no-op — RLS still applies on PostgreSQL.
- `creating` hooks auto-assign `tenant_id` from context when not explicitly set.

```php
use Platform\Tenancy\Support\TenantContext;

TenantContext::set($tenantId); // tests / jobs
// ...
TenantContext::clear();
```

### Migrations

RLS is applied in `2026_07_12_000002_enable_row_level_security_on_tenants_table.php`. The migration is a no-op on SQLite (local/package tests); run against PostgreSQL in CI and production.

### Testing

- Model CRUD tests run on SQLite.
- RLS isolation tests call `markTestSkipped()` when the driver is not `pgsql`.
- Full RLS verification belongs in the monorepo tenant-isolation suite (Vol 13 Ch. 04).

## Documentation

| Document | Description |
|----------|-------------|
| [ARCHITECTURE.md](./ARCHITECTURE.md) | Bounded context, dependencies, forbidden imports |
| [DATABASE.md](./DATABASE.md) | `tenants` table schema |
| [API.md](./API.md) | HTTP endpoints |
| [TESTING.md](./TESTING.md) | Test commands and coverage |
| [CHANGELOG.md](./CHANGELOG.md) | Semver history |

## References

- [Platform OS Ch. 13 §3](../../../docs/03-architecture/13-platform-os-architecture.md)
- [ADR-002 Tenant Isolation](../../../docs/00-meta/adr/002-tenant-isolation.md)
