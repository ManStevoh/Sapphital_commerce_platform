# Identity — Architecture

**Package:** `Platform/Identity`  
**Type:** Kernel  
**Document ID:** SCP-PLAT-ID-ARCH-001

## Bounded Context

Identity and access management: authentication (sessions, MFA), authorization (RBAC), organizations, users, teams, and admin impersonation (`Impersonation/` sub-package in Phase 1).

## Dependencies

| Package | Relationship |
|---------|--------------|
| `platform/kernel` | Required — module manager, events, API versioning |
| `platform/tenancy` | Consumed at runtime — tenant-scoped users (Phase 1.2) |

## Public Surfaces

| Surface | Sprint 0 | Phase 1+ |
|---------|----------|----------|
| HTTP API | `GET /api/v1/platform/identity/health` | Login, MFA, RBAC, user CRUD |
| Domain events | — | `UserRegistered`, `RoleAssigned` |
| Policies | — | Per-resource `can:` middleware |

## Forbidden Imports

- `Modules/*` — Commerce, ERP, CRM, Learning domain models
- `Connectors/*` — external adapters (OAuth connectors register via kernel contracts)
- Direct cross-package Eloquent from Commerce or other products

## Ownership

Platform Kernel team — see Platform OS Ch. 13 §17.
