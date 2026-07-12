# Tenancy — Architecture

**Package:** `Platform/Tenancy`  
**Type:** Kernel  
**Document ID:** SCP-PLAT-TEN-ARCH-001

## Bounded Context

Tenant lifecycle and registry: organizations as isolated tenants, slug resolution, status, plan association, and country defaults (Nigeria-first).

## Dependencies

| Package | Relationship |
|---------|--------------|
| `platform/kernel` | Required — module manager, events, API versioning |

## Public Surfaces

| Surface | Sprint 0 | Phase 1+ |
|---------|----------|----------|
| HTTP API | `GET /api/v1/platform/tenancy/health` | Tenant CRUD, context middleware |
| Domain events | — | `TenantCreated`, `TenantSuspended` |
| Middleware | — | `tenant` context resolver |

## Forbidden Imports

- `Modules/*` — Commerce, ERP, CRM, Learning domain models
- `Connectors/*` — external adapters
- Direct cross-package Eloquent access from other packages (use published interfaces/events)

## Ownership

Platform Kernel team — see Platform OS Ch. 13 §17.
