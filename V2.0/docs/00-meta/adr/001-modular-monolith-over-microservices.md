# ADR-001: Modular Monolith over Microservices

**Date:** 2026-07-12  
**Status:** Accepted  
**Deciders:** Stephen Musyoka Makola  
**Volume Reference:** Volume 3 — Architecture

## Context

SCP is an enterprise SaaS commerce platform targeting thousands of merchants. The team starts small (1–5 engineers) with a 3-month MVP timeline. We must choose between:

- **Microservices** — independent deployable services per domain
- **Modular monolith** — single deployable application with strict internal boundaries
- **Traditional monolith** — single application with no internal boundaries

Industry evidence shows that premature microservices increase operational complexity 3–5x without proportional benefit at low scale. Shopify, Stripe, and GitHub all started as monoliths and extracted services when specific domains justified independent scaling.

## Decision

**Build SCP as a modular monolith** using Laravel with strict domain package boundaries, event-driven internal communication, and a documented service extraction path for high-load domains.

Physical layout follows **ADR-023 (Platform OS)**: `Platform/` for kernel and services, `Modules/` for business products, `Connectors/` for external adapters — packages live **outside** `app/`.

## Alternatives Considered

| Alternative | Pros | Cons | Why Rejected |
|-------------|------|------|--------------|
| Microservices from day one | Independent scaling, team autonomy | Requires DevOps team, distributed tracing, service mesh, network latency between services | Team size (1–5), MVP timeline (3 months), no proven scale yet |
| Traditional monolith (no boundaries) | Fastest initial development | Becomes unmaintainable; no extraction path; god classes inevitable | Violates modular and decoupled principles |
| Serverless (Lambda/Cloud Functions) | Auto-scaling, pay-per-use | Cold starts, vendor lock-in, complex local development, poor fit for Laravel | PHP/Laravel ecosystem mismatch |

## Consequences

### Positive

- Single deployment artifact — simpler CI/CD, debugging, and local development
- ACID transactions across domains (orders + inventory + payments) without distributed transaction complexity
- Faster iteration for small team
- Clear module boundaries enable future extraction without rewrite
- Lower infrastructure cost in Phase 1 (single server)

### Negative

- Entire application scales together vertically until extraction
- All modules share deployment cycle (mitigated by feature flags)
- Risk of boundary erosion over time (mitigated by architecture reviews and linting rules)

### Neutral

- Team must learn and enforce domain boundary conventions
- Module folder structure is more complex than flat Laravel app

## Engineering Principles Impact

| Principle | Impact |
|-----------|--------|
| UX First | Positive — faster shipping means faster UX iteration |
| Performance | Neutral — Octane compensates; extraction path for bottlenecks |
| API-First | Positive — modules expose APIs regardless of deployment model |
| Modular | Core decision — enforces module boundaries within monolith |
| Decoupled | Requires discipline — events and interfaces between modules |
| AI Native | Positive — AI module can be extracted first when load justifies |
| Secure by Default | Positive — single security perimeter easier to audit initially |
| Multi-Tenant | Positive — tenant context in single middleware layer |
| Extensible | Neutral — plugin system works regardless of deployment model |
| Observable | Simpler initially — single application to instrument |

## Performance Implications

- Laravel Octane (FrankenPHP) provides 2–10x throughput over traditional PHP-FPM
- No inter-service network latency
- Shared Redis cache and PostgreSQL connection pool
- Extraction candidates when p95 exceeds targets: Search, AI, Notifications

## Security Implications

- Single attack surface to harden and audit
- Simpler secret management (one application)
- Module boundaries prevent direct cross-domain data access
- Service extraction adds security boundaries later (network policies)

## Operational Implications

- Phase 1: Docker Compose on single VPS ($20–50/month)
- Phase 2: Separate queue workers, read replicas
- Phase 3: Extract search and AI services; load balancer for app servers
- Phase 4: Kubernetes when team size and scale justify operational cost

## Migration Path

```text
Phase 1: Modular Monolith (all domains in one Laravel app)
    ↓ (when search p95 > 100ms at scale)
Phase 2: Extract Search Service (Meilisearch already external)
    ↓ (when AI queue depth consistently high)
Phase 3: Extract AI Service (independent scaling, GPU nodes)
    ↓ (when notification volume exceeds worker capacity)
Phase 4: Extract Notification Service
    ↓ (remaining domains stay in core monolith unless justified)
```

Extraction criteria: domain exceeds 30% of total resource consumption OR requires different scaling characteristics (GPU, high concurrency).

## References

- [ADR-023: Platform OS](./023-sapphital-platform-os.md)
- Shopify engineering blog: "Deconstructing the Monolith" (2022)
- Stripe: monolith-first approach documented in engineering talks
- "MonolithFirst" — Martin Fowler
- Laravel Octane benchmarks: https://laravel.com/docs/octane
