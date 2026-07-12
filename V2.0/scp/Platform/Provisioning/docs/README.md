# Platform Provisioning

**Package:** `platform/provisioning`  
**Version:** 0.2.0  
**Layer:** Platform Kernel (Layer 1)  
**Traceability:** ADR-022, SCP-TASK-0005, Platform OS Ch. 13, Vol 16 Ch. 10

## Purpose

Tenant Provisioning Engine (TPE) orchestrates post-signup tenant onboarding: store settings, theme assignment, sample catalog seed, pages, and payment placeholder configuration.

## Phase 1.4 Scope (SCP-TASK-0005)

- `provisioning_runs` saga state table
- `ProvisionTenantJob` async pipeline (sync in tests)
- `ProvisionTenantService` orchestration
- Public signup API (`POST /api/v1/signup`)
- Provisioning status polling (`GET /api/v1/provisioning/{tenantId}/status`)

## Dependencies

| Package | Usage |
|---------|-------|
| `Platform/Tenancy` | `Tenant` model, tenant status lifecycle |
| `Platform/Billing` | `Plan`, `Subscription` trial creation |
| `Platform/Identity` | Optional — uses `MerchantUser` when present; falls back to direct `merchant_users` insert |

## API

### `POST /api/v1/signup` (public)

Creates tenant in `provisioning` state, merchant owner user, trial subscription, and dispatches provisioning job.

**Request:**

```json
{
  "email": "merchant@example.com",
  "password": "password123",
  "store_name": "Lagos Tech Shop",
  "plan_slug": "starter"
}
```

**Response:** `202 Accepted`

```json
{
  "tenant_id": "uuid",
  "provisioning_run_id": "uuid",
  "status": "provisioning",
  "poll_url": "/api/v1/provisioning/{tenantId}/status"
}
```

### `GET /api/v1/provisioning/{tenantId}/status`

Returns latest provisioning run status and step checklist.

**Response:** `200 OK`

```json
{
  "tenant_id": "uuid",
  "provisioning_run_id": "uuid",
  "status": "completed",
  "steps": {
    "create_default_store_settings": { "completed": true, "data": { "currency": "NGN", "timezone": "Africa/Lagos" } },
    "assign_theme": { "completed": true, "data": { "theme": "scp-dawn" } },
    "seed_sample_products": { "completed": true, "data": { "products": [] } },
    "create_pages": { "completed": true, "data": { "pages": [] } },
    "configure_paystack_placeholder": { "completed": true, "data": { "provider": "paystack", "mode": "test" } }
  },
  "started_at": "ISO8601",
  "completed_at": "ISO8601",
  "error": null
}
```

## Provisioning Steps

| Step | Phase 1 behavior |
|------|------------------|
| `create_default_store_settings` | JSON stub: NGN, Africa/Lagos |
| `assign_theme` | Assign `scp-dawn` placeholder |
| `seed_sample_products` | 3 sample products as JSON stub |
| `create_pages` | About + Contact page stubs |
| `configure_paystack_placeholder` | Paystack test-mode placeholder |

On success, tenant status transitions `provisioning` → `trial` (Vol 16 Ch. 02).

## Integration Notes

- Signup route is **public** — no `tenant.context` middleware
- Queue driver `sync` in tests runs job inline; production uses `provisioning` queue (Phase 1b)
- Identity package optional at runtime: signup works with direct DB insert when `MerchantUser` class is unavailable
- Billing plans seeded by `Platform/Billing` migration (`starter`, `growth`, `pro`)
- Status polling used by marketing signup funnel (Vol 16 Ch. 12) until WebSocket progress ships

## Testing

```bash
cd Platform/Provisioning
composer install
vendor/bin/phpunit
```

Feature tests: `tests/Feature/Provisioning/`

## References

- [Platform OS Ch. 13 §3](../../../docs/03-architecture/13-platform-os-architecture.md)
- [ADR-022 Tenant Provisioning](../../../docs/00-meta/adr/022-tenant-provisioning-engine.md)
- [Vol 16 Ch. 10 — TPE](../../../docs/16-saas-multi-tenancy/10-tenant-provisioning-engine.md)
- [Vol 16 Ch. 02 — Tenant Lifecycle](../../../docs/16-saas-multi-tenancy/02-tenant-lifecycle.md)
