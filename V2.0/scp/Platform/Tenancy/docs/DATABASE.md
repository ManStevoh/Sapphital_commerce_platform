# Tenancy — Database

**Package:** `Platform/Tenancy`  
**Document ID:** SCP-PLAT-TEN-DB-001

## Tables

### `tenants`

Tenant registry. RLS policies deferred to Phase 1.1 (ADR-002).

| Column | Type | Constraints | Notes |
|--------|------|-------------|-------|
| `id` | `uuid` | PK | Generated application-side |
| `slug` | `varchar` | UNIQUE, NOT NULL | URL-safe tenant identifier |
| `name` | `varchar` | NOT NULL | Display name |
| `status` | `varchar` | NOT NULL | e.g. `active`, `suspended`, `provisioning` |
| `plan_id` | `uuid` | NULLABLE | FK to `Platform/Billing` plans (Phase 1.3) |
| `country` | `char(2)` | NOT NULL, default `NG` | ISO 3166-1 alpha-2 |
| `created_at` | `timestamp` | NOT NULL | |
| `updated_at` | `timestamp` | NOT NULL | |

### Indexes (planned Phase 1.1)

- `tenants_slug_unique` — unique on `slug`
- `tenants_status_idx` — filter by status for platform admin

### RLS

Not enabled in Sprint 0 scaffold. Phase 1.1 adds tenant-scoped RLS per ADR-002 and Vol 3 Ch. 05.

### Relationships

- `plan_id` → `plans.id` (`Platform/Billing`) — added when billing package ships

## Migration

`database/migrations/2026_07_12_000001_create_tenants_table.php`
