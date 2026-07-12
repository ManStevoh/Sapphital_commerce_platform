# Module Template

**Document ID:** SCP-META-MOD-001  
**Version:** 1.0.0  
**Status:** ✅ Active  
**Traceability:** ADR-023, Vol 3 Ch. 13  

---

## Purpose

Blueprint for every installable package under `Platform/`, `Modules/`, `Connectors/`, `AI/`, or `Themes/`. **No package exists without this scaffold.**

---

## 1. Before You Scaffold

- [ ] Package listed in [Platform OS Ch. 13](../03-architecture/13-platform-os-architecture.md)
- [ ] ADR or volume chapter defines scope
- [ ] Dependency graph updated in [Knowledge Graph](./implementation-knowledge-graph.md)
- [ ] Owner team assigned (Ch. 13 §17)

---

## 2. Folder Structure

```text
{Platform|Modules|Connectors|AI}/{PackageName}/
├── src/
│   ├── Domain/
│   │   ├── Aggregates/
│   │   ├── ValueObjects/
│   │   ├── Events/
│   │   ├── Repositories/       # interfaces only
│   │   └── Exceptions/
│   ├── Application/
│   │   ├── Actions/
│   │   ├── DTOs/
│   │   ├── Listeners/
│   │   └── Queries/
│   ├── Infrastructure/
│   │   ├── Persistence/
│   │   ├── External/
│   │   └── Jobs/
│   └── Http/
│       ├── Controllers/
│       ├── Requests/
│       └── Resources/
├── database/
│   ├── migrations/
│   ├── seeders/
│   └── factories/
├── routes/
│   ├── api.php
│   └── web.php
├── resources/
│   └── views/                  # if admin UI fragments
├── tests/
│   ├── Unit/
│   └── Feature/
├── docs/                       # Module contract (mandatory)
│   ├── README.md
│   ├── ARCHITECTURE.md
│   ├── API.md
│   ├── DATABASE.md
│   ├── EVENTS.md
│   ├── PERMISSIONS.md
│   ├── CONFIG.md
│   ├── UI.md
│   ├── WORKFLOW.md
│   ├── TESTING.md
│   ├── UPGRADE.md
│   ├── CHANGELOG.md
│   └── TODO.md
├── module.json
├── composer.json
├── {PackageName}ServiceProvider.php
└── phpunit.xml                 # optional; or root workspace config
```

---

## 3. Required Files

| File | Purpose |
|------|---------|
| `module.json` | Machine manifest (name, semver, requires, permissions, routes) |
| `composer.json` | PSR-4 autoload `{PackageName}\\` → `src/` |
| `*ServiceProvider.php` | Register routes, policies, events from manifest |
| `docs/*` | Full module contract (ADR-023) |

### module.json minimum

```json
{
  "name": "PackageName",
  "slug": "package-name",
  "version": "0.1.0",
  "author": "SAPPHITAL",
  "type": "platform-service",
  "requires": {
    "kernel": ">=2.0",
    "platform/tenancy": ">=1.0"
  },
  "permissions": [],
  "providers": ["Platform\\PackageName\\PackageNameServiceProvider"],
  "routes": ["routes/api.php"],
  "migrations": "database/migrations",
  "events": {
    "publishes": [],
    "subscribes": []
  },
  "menus": [],
  "widgets": []
}
```

`type`: `kernel` | `platform-service` | `product` | `extension` | `connector` | `ai-skill` | `theme`

---

## 4. Required Interfaces (Connectors)

Connectors **must** implement a platform contract from `Packages/contracts/`:

```php
interface PaymentGatewayAdapter
{
    public function initializePayment(PaymentIntentDTO $intent): RedirectResponse;
    public function handleWebhook(Request $request): WebhookResult;
}
```

No Commerce imports in Connectors.

---

## 5. Required Tests

| Test type | Minimum |
|-----------|---------|
| Unit | Every Action + domain invariant |
| Feature | Every HTTP route (200 + 403 + 422) |
| Isolation | Cross-tenant access denied |
| Contract | Connector mock against interface |

Document commands in `docs/TESTING.md`.

---

## 6. Required Documentation Content

### ARCHITECTURE.md

- Bounded contexts inside package
- Dependencies (requires graph)
- Public surfaces (API + events)
- Forbidden imports

### API.md

Per endpoint:

```markdown
### POST /api/v1/products

**Permission:** `commerce.products.create`

**Request:** (schema)

**Response:** 201 + ProductResource

**Events:** ProductCreated

**Errors:** 422 validation, 403 forbidden

**Tests:** `tests/Feature/ProductStoreTest.php`
```

### DATABASE.md

Tables, columns, indexes, RLS policies, relationships — before first migration.

### EVENTS.md

Published and subscribed events with payload schema.

---

## 7. Required Routes

- Register only in `routes/api.php` / `web.php`
- Named routes: `{package}.{resource}.{action}`
- Middleware: `auth:sanctum`, `tenant`, `can:` policy

---

## 8. Required Policies

- One policy class per aggregate/resource
- Permissions registered in `module.json` → synced on enable

---

## 9. Required UI (if admin-facing)

Link to Vol 4 screen specs in `docs/UI.md`. List:

- Routes/screens
- Permissions
- Empty/loading/error states
- API endpoints consumed

---

## 10. Migration Strategy

- Forward-only migrations in package folder
- Module Manager runs on enable/upgrade
- Breaking changes documented in `UPGRADE.md` with semver major bump

---

## 11. Scaffold Checklist

- [ ] Folder structure matches §2
- [ ] `module.json` validates against schema
- [ ] All `docs/` files exist (stubs OK if linked to volume chapters)
- [ ] ServiceProvider registered in Laravel shell
- [ ] CI pipeline includes package test job
- [ ] Knowledge graph updated
- [ ] No forbidden cross-package imports

---

## References

- [Platform OS Ch. 13 §12](../03-architecture/13-platform-os-architecture.md)
- [Engineering Standards](./engineering-standards.md)
