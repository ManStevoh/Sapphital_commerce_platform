# Tenancy — Testing

**Package:** `Platform/Tenancy`  
**Document ID:** SCP-PLAT-TEN-TEST-001

## Prerequisites

Package tests use [Orchestra Testbench](https://github.com/orchestral/testbench) for isolated Laravel package testing.

```bash
cd V2.0/scp/Platform/Tenancy
composer install   # when shell dependencies are wired
./vendor/bin/phpunit
```

From monorepo root (once workspace `composer.json` aggregates packages):

```bash
./vendor/bin/phpunit --configuration V2.0/scp/Platform/Tenancy/phpunit.xml
```

## Coverage (Sprint 0)

| Test | File | Asserts |
|------|------|---------|
| Health endpoint | `tests/Feature/HealthTest.php` | 200, JSON `status` + `package` |

## Phase 1 Additions

- Tenant isolation (cross-tenant access denied)
- Migration smoke test against PostgreSQL
- RLS policy verification per ADR-002
