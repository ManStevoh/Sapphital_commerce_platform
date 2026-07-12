# ADR-002: Multi-Tenancy Strategy — Shared Database with RLS

**Date:** 2026-07-12  
**Status:** Accepted  
**Deciders:** Stephen Musyoka Makola  
**Volume Reference:** Volume 7 — SaaS, Multi-Tenancy & Billing

## Context

SCP is a multi-tenant SaaS platform where each merchant (tenant) must have complete data isolation. We must choose a tenancy model that balances:

- **Isolation strength** — preventing cross-tenant data access
- **Operational cost** — infrastructure per tenant vs. shared
- **Development complexity** — query patterns, migrations, backups
- **Scalability** — from 10 to 100,000 tenants
- **Enterprise requirements** — some clients may require dedicated infrastructure

## Decision

**Phase 1–3:** Shared PostgreSQL database with `tenant_id` column on all tenant-scoped tables, enforced by:

1. Global Eloquent scopes (automatic `WHERE tenant_id = ?`)
2. PostgreSQL Row-Level Security (RLS) policies as defense-in-depth
3. Tenant context middleware injected on every request
4. Tenant ID in all cache keys, queue payloads, and log contexts

**Phase 4 (Enterprise tier):** Schema-per-tenant or dedicated database for enterprise clients requiring data residency or compliance isolation.

## Alternatives Considered

| Alternative | Pros | Cons | Why Rejected |
|-------------|------|------|--------------|
| Database per tenant | Strongest isolation | 10K tenants = 10K databases; migration nightmare; connection pool exhaustion | Operationally impossible at target scale |
| Schema per tenant | Good isolation, shared server | 10K schemas complex to manage; migration must run per schema | Reserved for enterprise tier only |
| Shared DB, app-level only | Simplest implementation | Single bug leaks all tenant data; no DB-level safety net | Insufficient security for commerce platform |
| Separate app instance per tenant | Complete isolation | Cannot scale SaaS model; per-tenant deployment cost | Not SaaS — this is on-premise |

## Consequences

### Positive

- Single database to manage, backup, and migrate
- Efficient resource utilization (most tenants are small)
- Simple query patterns with automatic scoping
- RLS provides defense-in-depth even if application code has bugs
- Clear upgrade path to schema-per-tenant for enterprise

### Negative

- "Noisy neighbor" risk — one tenant's heavy queries affect others (mitigated by query timeouts, rate limiting)
- All tenants share database maintenance windows
- RLS adds slight query overhead (~1–3%)
- Requires rigorous testing for tenant isolation

### Neutral

- Every table design must include `tenant_id`
- Migrations are simpler (one schema to update)

## Engineering Principles Impact

| Principle | Impact |
|-----------|--------|
| Secure by Default | RLS ensures DB-level isolation even if app layer fails |
| Multi-Tenant | Core decision — defines isolation model |
| Performance | Minor overhead from RLS; mitigated by indexing tenant_id |
| Modular | Each module's models include tenant scoping |
| Observable | Tenant ID in all logs enables per-tenant debugging |

## Performance Implications

- Index all `tenant_id` columns (composite indexes with frequently queried columns)
- Connection pooling via PgBouncer
- Query timeout: 30 seconds maximum
- Per-tenant rate limiting prevents noisy neighbor
- Read replicas in Phase 2 for analytics queries

## Security Implications

- RLS policies: `USING (tenant_id = current_setting('app.tenant_id')::uuid)`
- Middleware sets `app.tenant_id` on every database connection
- Automated test suite attempts cross-tenant access (must fail)
- Audit log includes tenant_id on every entry
- Enterprise tier: schema-per-tenant removes shared-table risk entirely

## Operational Implications

- Single backup strategy covers all tenants
- Migrations run once against shared schema
- Tenant data export: `WHERE tenant_id = ?` query
- Tenant deletion: soft delete with 30-day recovery, then hard delete job
- Monitoring: per-tenant query volume and storage alerts

## Migration Path

```text
Phase 1: Shared DB + tenant_id + RLS (all tiers)
    ↓ (enterprise client requires dedicated isolation)
Phase 4: Schema-per-tenant (Enterprise tier only)
    ↓ (enterprise client requires data residency in specific region)
Phase 4+: Dedicated database in target region (Enterprise tier)
```

## References

- Citus Data: multi-tenant schema design patterns
- PostgreSQL RLS documentation
- Laravel Tenancy package (evaluated; custom implementation preferred for control)
- Shopify: shared database with shop_id (analogous pattern at scale)
