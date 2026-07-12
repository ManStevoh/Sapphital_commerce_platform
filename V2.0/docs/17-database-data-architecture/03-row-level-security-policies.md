# Chapter 03: Row-Level Security Policies

**Document ID:** SCP-DB-001-03  
**Version:** 1.0.0  
**Status:** ✅ Active  
**Traceability:** ADR-002, ADR-005, ADR-010, NFR-063  

---

## Purpose

Specify **RLS policies** and session context rules ensuring strict tenant isolation on shared PostgreSQL.

## Scope

- Session variables
- Policy templates
- Role matrix
- Platform admin and impersonation
- Testing requirements

---

## 1. Session Variables

| Variable | Set by | Purpose |
|----------|--------|---------|
| `app.tenant_id` | Middleware / worker | Default tenant filter |
| `app.user_id` | Auth | Audit attribution |
| `app.role` | Auth | Policy branch (`shopper`, `merchant_staff`, `platform_admin`) |
| `app.impersonation_tenant_id` | Admin guard | Scoped admin access (ADR-010) |

Helper function:

```sql
CREATE OR REPLACE FUNCTION app_current_tenant_id() RETURNS UUID AS $$
  SELECT NULLIF(current_setting('app.tenant_id', true), '')::UUID;
$$ LANGUAGE sql STABLE;
```

---

## 2. Enable RLS Template

```sql
ALTER TABLE commerce_orders ENABLE ROW LEVEL SECURITY;
ALTER TABLE commerce_orders FORCE ROW LEVEL SECURITY;

CREATE POLICY tenant_isolation ON commerce_orders
  FOR ALL
  USING (tenant_id = app_current_tenant_id())
  WITH CHECK (tenant_id = app_current_tenant_id());
```

Apply to **every** table containing `tenant_id`. No exceptions without ADR.

---

## 3. Role-Specific Policies

### Merchant staff

Uses `app.tenant_id` from JWT/session. Full CRUD within tenant.

### Storefront shopper

Read-only policies on published catalog tables; cart scoped to `app.user_id` or session token.

```sql
CREATE POLICY shopper_cart ON commerce_carts
  FOR ALL
  USING (
    tenant_id = app_current_tenant_id()
    AND (customer_id = app_current_user_id() OR session_id = app_current_session_id())
  );
```

### Platform admin (break-glass)

Separate policy using impersonation tenant:

```sql
CREATE POLICY platform_admin_read ON commerce_orders
  FOR SELECT
  USING (
    current_setting('app.role', true) = 'platform_admin'
    AND tenant_id = NULLIF(current_setting('app.impersonation_tenant_id', true), '')::UUID
  );
```

All platform admin queries logged to `audit_admin_access` (ADR-009, ADR-010).

---

## 4. Tables Without Direct tenant_id

Join tables inherit tenant via parent FK check in policy or denormalized `tenant_id` (preferred for performance).

---

## 5. PgBouncer + SET LOCAL (ADR-005)

```php
// Laravel middleware pseudocode
DB::transaction(function () {
    DB::statement("SET LOCAL app.tenant_id = ?", [$tenantId]);
    DB::statement("SET LOCAL app.user_id = ?", [$userId]);
    DB::statement("SET LOCAL app.role = ?", [$role]);
    // ... queries
});
```

**Never** use `SET` without `LOCAL` under transaction pooling.

---

## 6. Bypass Roles

| Role | RLS | Use |
|------|-----|-----|
| `scp_app` | Subject to RLS | Application runtime |
| `scp_migration` | BYPASSRLS | Migrations only, CI locked |
| `scp_replica_bi` | Read-only replica | Analytics queries |

---

## 7. Verification Suite

Volume 13 tenant isolation tests must include:

- Cross-tenant SELECT returns zero rows
- INSERT with wrong `tenant_id` fails WITH CHECK
- Missing `SET LOCAL` fails closed (no rows / error)
- Impersonation cannot access without audit row

---

## Cross-References

- ADR-002, ADR-005, ADR-010
- [Volume 13 Ch. 04 — Tenant Isolation Tests](../13-testing/04-tenant-isolation-test-suite.md)
