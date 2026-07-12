# ADR-005: RLS Session Context with PgBouncer

**Date:** 2026-07-12  
**Status:** Accepted  
**Deciders:** Stephen Musyoka Makola  
**Volume Reference:** ADR-002; Volume 11 — Security

## Context

PostgreSQL Row-Level Security (RLS) policies depend on `current_setting('app.tenant_id')`. PgBouncer is used for connection pooling (NFR performance targets). With **transaction pooling**, a session-level `SET app.tenant_id = '...'` can leak tenant context to the next transaction on the same connection — a critical cross-tenant isolation failure.

## Decision

1. Use **`SET LOCAL app.tenant_id`** at the **start of every database transaction**, never session-level `SET`.
2. Laravel middleware/service wraps each request's DB work in a transaction boundary that sets `SET LOCAL` before queries run.
3. Queue workers **re-assert** tenant context from the job payload and use `SET LOCAL` before processing.
4. PgBouncer mode: **transaction pooling** (preferred for efficiency) with `SET LOCAL` discipline; session pooling only if a specific workload cannot use transaction boundaries (document exception in runbook).
5. CI tenant-isolation suite includes a test that simulates pooled connection reuse across tenants.

## Alternatives Considered

| Alternative | Pros | Cons | Why Rejected |
|-------------|------|------|--------------|
| Session pooling + `SET` | Simple per-request | Poor pool utilization; connection exhaustion at scale | Operational cost |
| App-level only (no RLS) | No SET complexity | Single bug = total breach | Violates ADR-002 defense-in-depth |
| Database per tenant | No RLS needed | Not scalable for SaaS | See ADR-002 |

## Consequences

### Positive

- RLS remains effective with efficient pooling
- Known footgun explicitly documented and tested

### Negative

- All DB access must respect transaction boundaries
- Long-running jobs need careful tenant re-binding

## Security Implications

- Failure to use `SET LOCAL` is classified as **SEV1** isolation risk
- `FORCE ROW LEVEL SECURITY` on all tenant tables (ADR-002)

## References

- PostgreSQL RLS: https://www.postgresql.org/docs/current/ddl-rowsecurity.html
- PgBouncer pooling modes: https://www.pgbouncer.org/features.html
- NFR-040
