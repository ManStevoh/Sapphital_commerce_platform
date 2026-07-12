# Platform Identity

**Package:** `platform/identity`  
**Version:** 0.2.0  
**Layer:** Platform Kernel (Layer 1)  
**Traceability:** ADR-006, ADR-023, Platform OS Ch. 13, SCP-TASK-0003

## Purpose

Authentication, authorization, roles, permissions, organizations, users, and teams for the SAPPHITAL Platform OS.

## Phase 1.2 Scope (SCP-TASK-0003)

- Identity tables: `merchant_users`, `platform_admins`, `customers` (tenant-scoped where applicable)
- Eloquent models with UUID primary keys and password hiding
- Multi-guard auth configuration (`merchant`, `platform`, `customer`, `api`/Sanctum)
- Sanctum token login for merchant and platform admins
- `GET /api/v1/auth/me` for authenticated principal resolution
- `permission.check` middleware stub (always passes until RBAC lands)

## Auth Guards

| Guard | Driver | Provider | Model |
|-------|--------|----------|-------|
| `merchant` | session | `merchant_users` | `MerchantUser` |
| `platform` | session | `platform_admins` | `PlatformAdmin` |
| `customer` | session | `customers` | `Customer` |
| `api` | sanctum | `merchant_users` | token-authenticated principals |

Phase 1 login endpoints issue Sanctum personal access tokens for API simplicity.

## Documentation

| Document | Description |
|----------|-------------|
| [ARCHITECTURE.md](./ARCHITECTURE.md) | Bounded context, dependencies, forbidden imports |
| [API.md](./API.md) | HTTP endpoints |
| [CHANGELOG.md](./CHANGELOG.md) | Semver history |

## References

- [Platform OS Ch. 13 §3](../../../docs/03-architecture/13-platform-os-architecture.md)
- [ADR-006 Authentication & Authorization](../../../docs/00-meta/adr/006-authentication-stack.md)
- [Vol 3 Ch. 06 — Identity & Access](../../../docs/03-architecture/06-identity-and-access.md)
