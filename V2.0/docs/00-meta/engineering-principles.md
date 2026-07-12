# Engineering Principles

These principles govern every architectural, product, and implementation decision across the SAPPHITAL Commerce Platform. All volumes, modules, ADRs, and code reviews must demonstrate compliance with these principles or document explicit exceptions with justification.

---

## 1. User Experience First

**Principle:** If a decision makes the software slower, harder to use, or less intuitive, it must be questioned and justified.

**Implementation requirements:**

- Every screen must have defined loading, empty, error, and success states
- Interaction patterns must be consistent across admin, vendor, and storefront surfaces
- Accessibility (WCAG 2.2 AA) is a product requirement, not a polish item
- User research and usability testing inform major UX decisions
- Design system components are the only approved UI building blocks

**Measurement:**

- System Usability Scale (SUS) target: ≥ 80
- Task completion rate: ≥ 95% for core flows (signup, add product, checkout)
- Customer Effort Score (CES): ≤ 2.0 for support interactions

---

## 2. Performance by Default

**Principle:** Every page loads in under 2 seconds on a typical broadband connection, with meaningful content appearing much sooner.

**Performance budgets:**

| Surface | LCP | FID/INP | CLS | TTI |
|---------|-----|---------|-----|-----|
| Storefront (product page) | ≤ 1.8s | ≤ 100ms | ≤ 0.05 | ≤ 2.5s |
| Storefront (homepage) | ≤ 2.0s | ≤ 100ms | ≤ 0.05 | ≤ 2.5s |
| Admin dashboard | ≤ 2.0s | ≤ 150ms | ≤ 0.1 | ≤ 3.0s |
| API (p95 read) | — | — | — | ≤ 200ms |
| API (p95 write) | — | — | — | ≤ 500ms |
| Search (autocomplete) | — | — | — | ≤ 100ms |

**Resource budgets (storefront):**

- JavaScript: ≤ 150 KB gzipped (initial load)
- CSS: ≤ 50 KB gzipped
- Fonts: ≤ 100 KB (subset, preloaded)
- Hero image: ≤ 200 KB (WebP/AVIF, responsive)

**Implementation requirements:**

- SSR/ISR for storefront pages (Next.js)
- Laravel Octane for API throughput
- Redis caching with defined TTLs per resource type
- CDN for all static assets and theme assets
- Database query budgets: no N+1; p95 query ≤ 50ms

---

## 3. API-First

**Principle:** Every feature must be consumable through APIs. The web UI is one client among many.

**Clients that consume the same APIs:**

- Storefront (Next.js)
- Admin panel (Next.js)
- Vendor portal (Next.js)
- Mobile apps (React Native)
- POS terminals
- AI agents
- Third-party integrations
- Webhooks consumers

**Implementation requirements:**

- No business logic exclusively in frontend code
- OpenAPI 3.1 specification for every public endpoint
- GraphQL Storefront API for flexible merchant queries (Phase 2)
- Versioned APIs (`/api/v1/`) with deprecation policy
- Consistent error format (RFC 7807 Problem Details)
- Rate limiting on all public endpoints

---

## 4. Modular

**Principle:** Every module owns its domain. No module directly manipulates another module's database.

**Module communication rules:**

| Allowed | Not Allowed |
|---------|-------------|
| Domain events (async) | Direct cross-module DB queries |
| Published service interfaces | Importing another module's internal classes |
| REST/GraphQL API calls | Shared mutable state without contracts |
| Webhook dispatch | Circular module dependencies |

**Module structure (Laravel):**

```text
app/Domain/{ModuleName}/
├── Models/
├── Actions/
├── Services/
├── Repositories/
├── Events/
├── Policies/
├── DTOs/
├── ValueObjects/
└── Exceptions/
```

---

## 5. Decoupled

**Principle:** Dependencies always point inward. Business logic never depends on UI.

**Architecture layers (Clean Architecture):**

```text
┌─────────────────────────────────────┐
│  Presentation (Controllers, Views)  │
├─────────────────────────────────────┤
│  Application (Actions, Commands)    │
├─────────────────────────────────────┤
│  Domain (Entities, Value Objects)   │
├─────────────────────────────────────┤
│  Infrastructure (DB, Cache, Queue)  │
└─────────────────────────────────────┘
         Dependencies point DOWN ↑
```

**Rules:**

- Domain layer has zero framework dependencies
- Controllers are thin — delegate to Actions/Services
- No business rules in Blade/React components
- DTOs for all cross-layer data transfer
- Repository pattern for data access abstraction

---

## 6. AI Native

**Principle:** AI is infrastructure, not an optional feature.

**Every module must define:**

| Artifact | Description |
|----------|-------------|
| AI opportunities | Where AI adds measurable value |
| AI services | Specific capabilities exposed |
| AI permissions | What AI can/cannot access per role |
| AI memory | Context retention scope and TTL |
| AI actions | Callable tools with audit trail |

**AI infrastructure (platform-level):**

- Multi-model gateway (OpenAI, Anthropic, Gemini, local models)
- RAG pipeline with pgvector for tenant-scoped knowledge
- Agent orchestration with tool calling
- Prompt versioning and A/B testing
- Cost tracking per tenant
- Content moderation and safety filters

---

## 7. Secure by Default

**Principle:** Security is designed in, not bolted on.

**Every module must document:**

- Threat model (STRIDE analysis)
- Authorization matrix (RBAC + policies)
- Input validation rules
- Audit logging requirements
- Encryption requirements (at rest, in transit)
- Rate limiting thresholds

**Platform security baseline:**

- TLS 1.3 everywhere
- OWASP ASVS Level 2 compliance target
- CSRF protection on all state-changing operations
- CSP headers on all web surfaces
- Secrets in vault (never in code or env files in repo)
- Dependency scanning in CI/CD
- Penetration testing before major releases

---

## 8. Multi-Tenant

**Principle:** Everything is tenant-aware. No accidental cross-tenant access.

**Isolation model (Phase 1):**

- Shared database with `tenant_id` on all tenant-scoped tables
- PostgreSQL Row-Level Security (RLS) policies
- Tenant context injected at middleware layer
- Global scopes on all Eloquent models
- Tenant ID in all cache keys, queue jobs, and log contexts

**Verification:**

- Automated tests for cross-tenant access attempts (must fail)
- Tenant isolation audit in CI pipeline
- Migration path documented for schema-per-tenant (enterprise tier)

---

## 9. Extensible

**Principle:** Themes, plugins, webhooks, APIs, events, and SDKs extend the platform without modifying core code.

**Extension points:**

| Extension | Mechanism | Example |
|-----------|-----------|---------|
| Themes | Theme SDK + JSON templates | Merchant customizes storefront |
| Plugins | Plugin SDK + hook system | Custom shipping calculator |
| Webhooks | Event subscription | Order created → ERP sync |
| APIs | REST/GraphQL | Mobile app integration |
| Events | Domain event bus | Inventory update → search reindex |
| SDKs | npm/Composer packages | `@sapphital/commerce-sdk` |

**Rules:**

- Core code never imports plugin code directly
- Plugins run in sandboxed contexts with defined permissions
- Breaking changes to extension APIs require major version bump

---

## 10. Observable

**Principle:** Everything must be measurable.

**Three pillars:**

| Pillar | Tool | Scope |
|--------|------|-------|
| Logs | Structured JSON (Monolog → Loki/ELK) | All services |
| Metrics | Prometheus + Grafana | Latency, throughput, errors |
| Traces | OpenTelemetry | Request flows across modules |

**Required observability per module:**

- Health check endpoint
- Key business metrics (orders/min, conversion rate)
- Error rate alerting (threshold: > 1% for 5 min)
- Audit log for all state-changing operations
- Performance dashboards for p50/p95/p99

---

## Compliance Matrix

All subsequent volumes must include a section mapping their content to these principles:

```markdown
## Engineering Principles Compliance

| Principle | How This Volume Complies |
|-----------|--------------------------|
| UX First | ... |
| Performance | ... |
| API-First | ... |
| ... | ... |
```

---

## Exceptions

Any deviation from these principles requires:

1. An ADR documenting the exception
2. Approval from Lead Architect
3. A remediation plan with timeline
4. Explicit marking in affected module documentation
