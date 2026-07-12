# Engineering Standards

**Document ID:** SCP-META-STD-001  
**Version:** 1.1.0  
**Status:** ✅ Active  
**Traceability:** Vol 3 Ch. 04, Vol 3 Ch. 11, Vol 13, Vol 21 Ch. 09, ADR-001, ADR-023, NFR-001 – NFR-046  

---

## Purpose

Normative coding standards for SCP implementation. Cursor and human reviewers treat this as **binding**. Exceptions require ADR or documented module-level waiver in `docs/WAIVER.md`.

---

## 1. General Rules

| Rule | Standard |
|------|----------|
| Language (backend) | PHP 8.4+, `declare(strict_types=1);` in every PHP file |
| Language (frontend) | TypeScript strict mode; no `any` without comment |
| Framework | Laravel 12+ (thin shell); Next.js 15 App Router (clients) |
| Naming | PSR-12 (PHP); ESLint + Prettier (TS) |
| IDs in code | Reference FR-/NFR-/ADR- in class docblocks for traceability |
| Secrets | Never in repo; use `Platform/Secrets/` (ADR-007) |
| Money | Integer minor units + ISO 4217; never float |
| Tenant scope | Every tenant table has `tenant_id`; RLS enforced (ADR-002) |

---

## 2. File Size Limits

| Artifact | Max lines | Notes |
|----------|-----------|-------|
| Controller | 200 | Delegate to Actions |
| Action (use case) | 150 | Single public method `execute()` or `handle()` |
| Service | 300 | Split if multiple responsibilities |
| Model (Eloquent) | 200 | No business logic beyond accessors/scopes |
| React component | 250 | Extract hooks and subcomponents |
| Policy | 100 | One resource per policy class |
| Migration | 150 | One concern per migration |

If exceeded: refactor before merge unless waiver approved.

---

## 3. Layer Responsibilities

```text
Domain/          → Entities, value objects, domain events, invariants
Application/     → Actions, DTOs, listeners (orchestration)
Infrastructure/  → Eloquent, HTTP clients, queue jobs
Http/            → Controllers, Form Requests, API Resources (thin)
```

| Layer | Allowed | Forbidden |
|-------|---------|-----------|
| **Domain** | Pure PHP, domain events | Eloquent, HTTP, facades |
| **Application** | DTOs, Actions, interfaces | Direct DB from other packages |
| **Infrastructure** | Persistence, external APIs | Cross-package aggregate mutation |
| **Http** | Validation, response mapping | Business rules |

---

## 4. Required Patterns

### Actions (Application layer)

```php
final class CreateProductAction
{
    public function __construct(
        private ProductRepository $products,
        private EventDispatcher $events,
    ) {}

    public function execute(CreateProductDTO $dto): Product
    {
        // single use case
    }
}
```

- One Action = one use case
- Input/output via **DTOs** (readonly classes)
- Status enums via **PHP 8.1+ enums**, not magic strings

### Repositories

- Interface in `Domain/Repositories/`
- Implementation in `Infrastructure/Persistence/`
- **No business logic** — query and persist only

### Controllers

```php
public function store(StoreProductRequest $request, CreateProductAction $action): JsonResponse
{
    $product = $action->execute(CreateProductDTO::fromRequest($request));
    return ProductResource::make($product)->response()->setStatusCode(201);
}
```

### DTOs

- Readonly properties
- Factory: `fromRequest()`, `fromModel()` where needed
- No validation (Form Request handles input validation)

### Events

- Immutable domain events in `Domain/Events/`
- Past tense names: `ProductCreated`, `OrderPaid`
- Include `tenantId`, `occurredAt`, aggregate ID

---

## 5. Platform OS Boundary Rules (ADR-023)

| From | To | Allowed |
|------|-----|---------|
| `Platform/*` | `Modules/*` | **Never** |
| `Modules/Commerce` | `Platform/*` | Via published interfaces/services only |
| `Modules/A` | `Modules/B` | Events or published query interfaces only |
| `Connectors/*` | `Modules/*` | **Never** — implement platform contracts |
| `apps/*` | Domain logic | **Never** — HTTP API only |

CI must fail on forbidden imports (deptrac or custom script).

---

## 6. Testing Standards (Mandatory)

**Every code change is tested. Test failures are fixed before work is complete. No broken flows ship.**

| Layer | Tool | Requirement |
|-------|------|-------------|
| Domain / Actions | Pest (unit) | Every Action and domain rule |
| HTTP API | Pest (feature) | Every route: success + 403 + 422 |
| Tenant isolation | Pest + PostgreSQL | 100% pass on any data change |
| Storefront E2E | Playwright | Full user flows: checkout, product CRUD, auth |
| Frontend unit | Vitest | Hooks, utils, non-trivial components |

### Flow coverage

For each feature, test the **complete flow** — not an isolated unit in isolation:

- Happy path end-to-end
- Unauthorized / wrong tenant → 403, never 500 or empty wrong data
- Validation errors → 422 with structured errors
- Empty state (no records yet)
- Error state (API failure surfaced in UI — **no white screen**)

### Quality gates

- **Fix all failing tests** before merge — do not disable or skip tests to green CI
- **No HTTP 500** for expected business cases — use 4xx + RFC 7807 Problem Details
- **No white screens** — loading, error, and empty UI states required (Vol 4)
- **No unhandled exceptions** in production paths; log and return safe errors
- Run `./vendor/bin/pest` or scoped test files after backend changes

**Critical paths (100% branch coverage):** auth, payments, tenant context, RLS, webhook signatures.

**Build policy:** Do not run `npm run build`, `composer install`, or dev servers unless the user explicitly instructs (`.cursor/rules/no-npm-build.mdc`). Tests and lint are allowed and expected.

---

## 7. API Standards

- OpenAPI 3.1 for every public route before implementation
- Version prefix: `/api/v1/`
- Errors: RFC 7807 Problem Details
- Pagination: cursor-based for lists > 100 items
- Idempotency-Key header on POST that create money/state

Document in module `docs/API.md` before coding.

---

## 8. Database Standards

- Document in module `docs/DATABASE.md` **before** migration
- All tenant tables: `tenant_id UUID NOT NULL` + RLS policy
- Indexes on foreign keys and filter columns
- Soft delete: `deleted_at` where FR-025 applies
- Audit columns: `created_at`, `updated_at`; audit log for sensitive mutations (ADR-009)

See [Vol 17](../17-database-data-architecture/README.md).

---

## 9. Frontend Standards

- Design system components from Vol 4 only
- Every screen: loading, empty, error, success states
- WCAG 2.2 AA
- Document in Vol 4 or module `docs/UI.md` before building

UI spec minimum per screen:

- Header, filters, table/cards, pagination
- Modals/drawers, permissions, keyboard shortcuts
- Responsive breakpoints, error copy

---

## 10. AI Implementation Standards

- Prompts in `V2.0/docs/00-meta/prompts/`, versioned
- Code references `prompt_version_id`, not inline strings
- Every agent: purpose, inputs, outputs, tools, permissions documented (Vol 9)
- Tenant-scoped memory; no cross-tenant RAG

---

## 11. Documentation Standards

When code ships, update:

| Change type | Update |
|-------------|--------|
| New endpoint | module `API.md` + OpenAPI |
| New event | module `EVENTS.md` |
| New table | module `DATABASE.md` |
| New permission | module `PERMISSIONS.md` |
| Breaking change | `UPGRADE.md` + `CHANGELOG.md` |
| Architecture change | ADR + Vol 3 if platform-wide |

---

## 12. Security Checklist (Every PR)

- [ ] Authorization policy on new routes
- [ ] Tenant context set before queries
- [ ] No raw SQL with string concatenation
- [ ] Webhook signature verification
- [ ] SSRF protection on outbound HTTP (Vol 12 Ch. 11)
- [ ] PII logged only at redacted level
- [ ] Rate limiting on new public endpoints
- [ ] Fail-closed on auth/tenant errors (no silent empty results across tenants)

---

## 13. Performance Standards (Speed)

**North star:** Platform sustains **1M+ products, customers, and daily transactions** per growth phase without latency regression or outages. Phase 1 targets are in NFR-001–NFR-012; code must **not block** scaling to NFR Phase 4/5 levels.

| Area | Rule |
|------|------|
| API latency | p95 read ≤ 200ms, write ≤ 500ms (NFR-003/004) |
| Database | p95 query ≤ 50ms; explain analyze on new hot queries |
| N+1 | Forbidden — detect in CI where possible |
| Pagination | Cursor-based for lists; max page size enforced (default 25, max 100) |
| Caching | Cache-aside Redis; keys prefixed `{tenant_id}:` |
| HTTP request work | No bulk processing > 100 items synchronously |
| External calls | Timeouts + retries in jobs only; circuit breaker on Connectors |
| Storefront | LCP ≤ 2s mobile; lazy-load below fold; ISR/SSR per Vol 6 |
| Octane | No static mutable request state; use scoped bindings |

### Hot path checklist (checkout, auth, catalog list)

- [ ] Query count bounded (document expected count in TESTING.md)
- [ ] Indexes exist for WHERE/ORDER BY columns
- [ ] Response payload minimal (API Resources, no hidden eager loads)
- [ ] Non-critical side effects queued

---

## 14. Decoupling Standards

| Pattern | Use |
|---------|-----|
| Domain events | Cross-package side effects (search, notify, analytics) |
| Published interfaces | Narrow sync reads (e.g. catalog query by ID) |
| FSL / Platform services | Payments, tax, shipping — never direct connector imports |
| Outbox pattern | Reliable event delivery (Vol 17 Ch. 07) |
| Idempotency keys | Order create, payment init, webhook handlers |

**Forbidden:** cross-module Eloquent relationships, cross-schema JOINs, shared mutable singletons holding tenant data.

---

## 15. Scalability Standards (1M+ Ready)

Design every feature assuming **10×–100×** current volume without schema or API breaking changes.

| Dimension | Phase 1 | North star (design for) |
|-----------|---------|-------------------------|
| Active merchants | 500 | 100,000+ |
| Products per store | 10,000 | 1,000,000 |
| Orders per day (platform) | 1,000 | 1,000,000+ |
| API RPS | 100 | 5,000+ |
| Search documents | 1M | 100M |
| Concurrent users | 1,000 | 50,000+ |

### Implementation rules

- **UUID primary keys** for distributed-friendly IDs
- **Tenant-scoped indexes** composite: `(tenant_id, …)` on all hot queries
- **Partition-ready tables** — document partition key in DATABASE.md when row count may exceed 10M
- **Stateless API** — scale horizontally; no in-process session state
- **Queue segregation** — `payments`, `notifications`, `search`, `default` queues; heavy jobs never block critical
- **Read replicas** — read-only queries (reports, admin lists) route to replica when available (Phase 2+)
- **Graceful degradation** — if search/AI unavailable, core commerce still completes

See [Vol 3 Ch. 11 — Scalability](../03-architecture/11-scalability-and-service-extraction.md).

---

## 16. Reliability Standards (No Crashes)

| Requirement | Target |
|-------------|--------|
| Uptime | 99.9% monthly (NFR-021) |
| MTTR P1 | ≤ 30 minutes (NFR-023) |
| Zero-downtime deploy | Phase 2+ (NFR-028) |
| Backups | Every 6h; RPO ≤ 6h, RTO ≤ 4h (NFR-025–027) |

- Wrap external Connector calls; never let uncaught vendor exceptions crash workers
- Use database transactions for multi-table invariants inside one aggregate
- Dead-letter queue + alert for failed payment/notification jobs
- Health endpoints: `/health`, `/ready` (DB + Redis)

---

## References

- [Engineering Knowledge Base](./engineering-knowledge-base.md)
- [Vol 21 Ch. 09 — Engineering Standards Checklist](../21-implementation-playbooks/09-engineering-standards-checklist.md)
- [Vol 3 Ch. 11 — Scalability & Extraction](../03-architecture/11-scalability-and-service-extraction.md)
- [Vol 1 Ch. 09 — NFRs](../01-vision/09-non-functional-requirements.md)
