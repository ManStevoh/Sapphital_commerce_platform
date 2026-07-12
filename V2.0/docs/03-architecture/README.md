# Volume 3: System Architecture

**Document ID:** SCP-ARCH-001  
**Version:** 1.1.0  
**Status:** ✅ Active  
**Depends On:** Volume 1 (Vision), ADR-001 through ADR-023  
**Owner:** Sapphital Learning Company  
**Lead Architect:** Stephen Musyoka Makola  

---

## Purpose

Volume 3 is the **official architectural blueprint** for SAPPHITAL — a **Platform OS** (Products + Platform Services + Connectors), not a flat Laravel module list. SCP Commerce is one **business product** installed on the kernel (ADR-023).

This volume answers: *What are the major parts of SCP, how do they interact, and what rules must every module follow?*

## Scope

- C4 model views (Context, Container; Component patterns referenced per module)
- Architectural principles, constraints, and quality attributes
- Bounded contexts, module boundaries, and clean architecture layers
- Multi-tenancy, request lifecycle, authentication, and authorization
- Event-driven communication and data ownership contracts
- API architecture, versioning, and extensibility (themes, plugins, webhooks)
- Scalability path and service extraction criteria
- Deployment and runtime topology (FrankenPHP Octane, Cloudflare, Nigeria region)

## Out of Scope

- Detailed module business rules and state machines (Volume 5+)
- Infrastructure runbooks and CI/CD pipelines (Volume 10)
- Security compliance matrices and pentest procedures (Volume 11)
- Theme SDK implementation details (Volume 6)
- Developer portal and OAuth marketplace (Volume 12)

## Platform Identity

SCP is an **AI-native, multi-tenant, enterprise Commerce Operating System** for merchants, marketplaces, and enterprises across **Nigeria (primary)**, **Kenya/East Africa**, and broader African expansion.

**Architectural stance:**

| Attribute | Decision | ADR |
|-----------|----------|-----|
| Deployment model | Modular monolith + **Platform OS packages** | [ADR-001](../00-meta/adr/001-modular-monolith-over-microservices.md), [ADR-023](../00-meta/adr/023-sapphital-platform-os.md) |
| Tenancy | Shared PostgreSQL + RLS | [ADR-002](../00-meta/adr/002-multi-tenancy-shared-db-rls.md) |
| RLS + pooling | `SET LOCAL` per transaction | [ADR-005](../00-meta/adr/005-rls-pgbouncer-set-local.md) |
| Storefront themes | React + JSON schema | [ADR-003](../00-meta/adr/003-theme-engine-react-json-schema.md) |
| Checkout / PCI | PSP redirect (SAQ A) | [ADR-004](../00-meta/adr/004-checkout-psp-redirect-saq-a.md) |
| Authentication | Fortify + Sanctum | [ADR-006](../00-meta/adr/006-authentication-stack.md) |
| Edge security | Cloudflare | [ADR-008](../00-meta/adr/008-edge-security-cloudflare.md) |
| Data residency | Nigeria (Lagos) primary | [ADR-011](../00-meta/adr/011-data-residency-africa.md) |

## Chapters

| # | Chapter | Document ID | Status |
|---|---------|-------------|--------|
| 01 | [Architecture Overview](./01-architecture-overview.md) | SCP-ARCH-001-01 | ✅ Active |
| 02 | [Architectural Principles and Constraints](./02-architectural-principles-and-constraints.md) | SCP-ARCH-001-02 | ✅ Active |
| 03 | [Bounded Contexts and Modules](./03-bounded-contexts-and-modules.md) | SCP-ARCH-001-03 | ✅ Active |
| 04 | [Clean Architecture Layers](./04-clean-architecture-layers.md) | SCP-ARCH-001-04 | ✅ Active |
| 05 | [Multi-Tenancy and Isolation](./05-multi-tenancy-and-isolation.md) | SCP-ARCH-001-05 | ✅ Active |
| 06 | [Request Lifecycle and Auth](./06-request-lifecycle-and-auth.md) | SCP-ARCH-001-06 | ✅ Active |
| 07 | [Event-Driven Communication](./07-event-driven-communication.md) | SCP-ARCH-001-07 | ✅ Active |
| 08 | [API Architecture and Versioning](./08-api-architecture-and-versioning.md) | SCP-ARCH-001-08 | ✅ Active |
| 09 | [Data Ownership and Contracts](./09-data-ownership-and-contracts.md) | SCP-ARCH-001-09 | ✅ Active |
| 10 | [Extensibility: Themes, Plugins, Webhooks](./10-extensibility-themes-plugins-webhooks.md) | SCP-ARCH-001-10 | ✅ Active |
| 11 | [Scalability and Service Extraction](./11-scalability-and-service-extraction.md) | SCP-ARCH-001-11 | ✅ Active |
| 12 | [Deployment and Runtime Topology](./12-deployment-and-runtime-topology.md) | SCP-ARCH-001-12 | ✅ Active |
| 13 | [Platform OS Architecture](./13-platform-os-architecture.md) | SCP-ARCH-001-13 | ✅ Active |

## Technology Stack Summary

| Layer | Technology | Version / Notes |
|-------|------------|-----------------|
| Application runtime | PHP + Laravel | 8.4+ / Laravel 12+ |
| HTTP server | FrankenPHP + Laravel Octane | Persistent workers; 2–10× throughput vs FPM |
| Primary database | PostgreSQL | 16+ with RLS |
| Connection pool | PgBouncer | Transaction pooling + `SET LOCAL` |
| Cache / queues | Redis | Sessions, cache, rate limits, Horizon |
| Search | Meilisearch | Tenant-scoped indexes |
| Storefront | Next.js (App Router) | SSR/ISR; theme packages |
| Edge / CDN / WAF | Cloudflare | African PoPs; R2 storage |
| Observability | OpenTelemetry, Sentry, Prometheus | NFR-062 – NFR-068 |
| Primary region | Nigeria (Lagos) | ADR-011; Kenya region for KE merchants |

## Traceability

| Source | Relationship |
|--------|--------------|
| [Volume 1 — Domain Model](../01-vision/10-domain-model-overview.md) | Bounded contexts, entities, events |
| [Volume 1 — NFRs](../01-vision/09-non-functional-requirements.md) | Performance, security, scalability targets |
| [Volume 11 — Security](../11-security/README.md) | Defense-in-depth implementation |
| [ADR Index](../00-meta/adr/) | Binding architectural decisions |

## Volume Acceptance Criteria

Volume 3 is **complete for Phase 1** when:

- [ ] All 13 chapters published with Document IDs and no placeholder sections
- [ ] C4 Context and Container diagrams present in Chapter 01
- [ ] Every bounded context from Volume 1 mapped to a package in Chapters 03 and 13
- [ ] Multi-tenancy defense-in-depth documented with ADR-002 and ADR-005 references
- [ ] Request lifecycle diagram covers tenant resolution, auth, and RLS binding
- [ ] Event catalog cross-references Volume 1 domain events
- [ ] API versioning policy defined with OpenAPI 3.1 requirement
- [ ] Service extraction criteria from ADR-001 reflected in Chapter 11
- [ ] Deployment topology specifies Nigeria-primary region and FrankenPHP Octane
- [ ] Architecture review sign-off by Lead Architect

---

**Next volumes:** Volume 5 (Commerce Engine) implements module details within these boundaries. Volume 10 (Infrastructure) operationalizes Chapter 12.
