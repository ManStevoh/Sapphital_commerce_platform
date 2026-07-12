# Chapter 01: Architecture Overview

**Document ID:** SCP-ARCH-001-01  
**Version:** 1.0.0  
**Status:** ✅ Active  
**Traceability:** ADR-001, NFR-001 – NFR-028, FR-020 – FR-025  

---

## Purpose

Provide a system-level view of SCP using the C4 model. This chapter establishes the platform boundary, major containers, and data flows that all subsequent architecture chapters refine.

## Scope

- C4 Level 1 (System Context)
- C4 Level 2 (Container)
- High-level request and event flows
- Nigeria-primary deployment context

## Out of Scope

- Component-level diagrams per module (Volume 5+)
- Infrastructure sizing and runbooks (Volume 10)

---

## 1. System Context (C4 Level 1)

SCP sits between merchants, shoppers, payment providers, and third-party integrators. The platform is operated by Sapphital Learning Company with **primary compute and data residency in Nigeria (Lagos region)** per ADR-011.

```mermaid
C4Context
    title SCP System Context — Nigeria-Primary Commerce OS

    Person(merchant, "Merchant", "Nigerian/Kenyan business owner or staff")
    Person(shopper, "Shopper", "Customer browsing and purchasing")
    Person(admin, "Platform Admin", "Sapphital support and operations")
    Person(developer, "Developer", "Third-party app builder")

    System(scp, "SAPPHITAL Commerce Platform", "Multi-tenant SaaS commerce OS: catalog, checkout, orders, marketplace, themes, APIs")

    System_Ext(paystack, "Paystack / Flutterwave", "Nigeria payment gateways")
    System_Ext(mpesa, "M-Pesa / Paystack KE", "Kenya mobile money and cards")
    System_Ext(cloudflare, "Cloudflare", "CDN, WAF, R2 storage, Turnstile")
    System_Ext(ai, "AI Providers", "LLM inference for catalog, support")
    System_Ext(courier, "Shipping Couriers", "GIG, Kwik, Sendbox, etc.")
    System_Ext(sms, "SMS / Email", "Termii, Africa's Talking, transactional email")

    Rel(merchant, scp, "Manages store, products, orders", "HTTPS")
    Rel(shopper, scp, "Browses storefront, checkout", "HTTPS")
    Rel(admin, scp, "Support, impersonation, ops", "HTTPS + MFA")
    Rel(developer, scp, "Integrates via API/webhooks", "HTTPS + API token")

    Rel(scp, paystack, "Redirect checkout, webhooks", "HTTPS")
    Rel(scp, mpesa, "STK Push, webhooks", "HTTPS")
    Rel(scp, cloudflare, "Edge security, CDN, object storage", "HTTPS")
    Rel(scp, ai, "Prompts and completions", "HTTPS")
    Rel(scp, courier, "Rate quotes, tracking", "HTTPS")
    Rel(scp, sms, "OTP, order notifications", "HTTPS")

    UpdateLayoutConfig($c4ShapeInRow="3", $c4BoundaryInRow="1")
```

### Context Actors

| Actor | Primary Market | Interaction |
|-------|----------------|-------------|
| Merchant | Nigeria (primary), Kenya | Admin dashboard, mobile admin |
| Shopper | Nigeria, Kenya, Africa | Storefront (Next.js), mobile web |
| Platform Admin | Nigeria HQ | Separate auth guard, MFA, impersonation (ADR-010) |
| Developer | Global | REST API, webhooks, future OAuth apps |

### External Systems

| System | Role | PCI / Compliance |
|--------|------|------------------|
| Paystack, Flutterwave | Nigeria card, bank, USSD | Hosted/redirect — SAQ A (ADR-004) |
| M-Pesa, Paystack KE | Kenya payments | No card data on SCP |
| Cloudflare | Edge, CDN, R2, bot protection | Subprocessor per NDPA RoPA |
| AI providers | Descriptions, support assist | DPIA; no cross-tenant context |
| Couriers | Fulfillment integrations | Merchant-configured credentials |

---

## 2. Container Diagram (C4 Level 2)

SCP Phase 1 deploys as a **modular monolith** (ADR-001) with externalized search and edge services.

```mermaid
C4Container
    title SCP Container Diagram — Phase 1 Modular Monolith

    Person(merchant, "Merchant")
    Person(shopper, "Shopper")

    Container_Boundary(edge, "Edge — Cloudflare (ADR-008)") {
        Container(cdn, "CDN / WAF", "Cloudflare", "TLS 1.3, DDoS, rate limits, Turnstile")
        Container(r2, "Object Storage", "Cloudflare R2", "Media, theme assets, backups")
    }

    Container_Boundary(app, "Application — Nigeria Region (ADR-011)") {
        Container(octane, "SCP Core API", "Laravel + FrankenPHP Octane", "Modular monolith: all domain modules, REST API, webhooks ingress")
        Container(horizon, "Queue Workers", "Laravel Horizon", "Async jobs, domain event handlers, webhooks egress")
        Container(storefront, "Storefront Renderer", "Next.js", "SSR/ISR theme rendering, Storefront API client")
    }

    Container_Boundary(data, "Data Layer") {
        ContainerDb(postgres, "Primary Database", "PostgreSQL 16 + RLS", "Tenant-scoped transactional data")
        ContainerDb(redis, "Cache & Queues", "Redis", "Sessions, cache, rate limits, job queues")
        ContainerDb(meili, "Search Index", "Meilisearch", "Tenant-scoped product/content search")
    }

    Container_Boundary(obs, "Observability") {
        Container(otel, "Telemetry", "OpenTelemetry + Sentry", "Traces, metrics, error tracking")
    }

    Rel(shopper, cdn, "Storefront requests")
    Rel(merchant, cdn, "Admin API requests")
    Rel(cdn, storefront, "SSR pages")
    Rel(cdn, octane, "API, webhooks")
    Rel(storefront, octane, "Storefront API", "HTTPS/JSON")
    Rel(octane, postgres, "Reads/writes", "PgBouncer + SET LOCAL")
    Rel(octane, redis, "Cache, sessions")
    Rel(octane, meili, "Index queries")
    Rel(octane, r2, "Media URLs")
    Rel(horizon, postgres, "Job processing")
    Rel(horizon, redis, "Dequeue")
    Rel(octane, otel, "Spans, logs")
    Rel(horizon, otel, "Job traces")
```

### Container Responsibilities

| Container | Responsibility | Scaling (Phase 1 → 3) |
|-----------|----------------|------------------------|
| **SCP Core API** | HTTP API, admin, webhook ingress, domain logic | Vertical → horizontal replicas |
| **Queue Workers** | Events, notifications, search indexing, webhooks | Horizontal worker pool |
| **Storefront Renderer** | Theme SSR/ISR, edge-cacheable pages | CDN + multiple Next.js instances |
| **PostgreSQL** | System of record; RLS enforcement | Read replicas Phase 2 |
| **Redis** | Hot cache, sessions, queues | Sentinel/cluster Phase 3 |
| **Meilisearch** | Full-text search | Dedicated cluster; extractable service |
| **Cloudflare** | Security perimeter, static asset delivery | Managed scale |

---

## 3. Logical Architecture Layers

Within the SCP Core API container, code follows **clean architecture** (see Chapter 04):

```mermaid
flowchart TB
    subgraph Presentation
        HTTP[HTTP Controllers / Middleware]
        CLI[Artisan Commands]
        WH[Webhook Controllers]
    end

    subgraph Application
        UC[Use Cases / Actions]
        DTO[DTOs / Commands / Queries]
    end

    subgraph Domain
        AR[Aggregate Roots]
        VO[Value Objects]
        DE[Domain Events]
        POL[Policies / Invariants]
    end

    subgraph Infrastructure
        REPO[Eloquent Repositories]
        EXT[External Adapters]
        BUS[Event Bus / Queue]
    end

    HTTP --> UC
    WH --> UC
    CLI --> UC
    UC --> AR
    UC --> DE
    UC --> REPO
    DE --> BUS
    REPO --> AR
    EXT --> UC
```

**Rule:** Domain layer has zero dependencies on Laravel, HTTP, or database frameworks.

---

## 4. Primary Data Flows

### 4.1 Storefront Product Browse

```mermaid
sequenceDiagram
    participant S as Shopper
    participant CF as Cloudflare CDN
    participant NX as Next.js Storefront
    participant API as SCP Core API
    participant R as Redis
    participant PG as PostgreSQL

    S->>CF: GET /products/slug
    CF->>NX: Cache miss → origin
    NX->>API: GET /storefront/v1/products/{slug}
    API->>R: Check product cache
    alt Cache hit
        R-->>API: Cached product
    else Cache miss
        API->>PG: SELECT (tenant scoped + RLS)
        PG-->>API: Product row
        API->>R: Store cache (tenant-prefixed key)
    end
    API-->>NX: JSON product
    NX-->>CF: SSR HTML
    CF-->>S: Page (LCP target ≤ 2.0s mobile)
```

### 4.2 Checkout and Payment (Redirect Model)

```mermaid
sequenceDiagram
    participant S as Shopper
    participant API as SCP Core API
    participant PG as PostgreSQL
    participant PSP as Paystack

    S->>API: POST /storefront/v1/checkout
    API->>PG: Create order (pending_payment)
    API->>PSP: Initialize transaction (server-side)
    PSP-->>API: authorization_url
    API-->>S: Redirect to PSP hosted page
    S->>PSP: Complete payment (no card data on SCP)
    PSP->>API: Webhook charge.success
    API->>API: Verify HMAC + idempotency
    API->>PG: Update order (paid)
    API->>API: Dispatch OrderPaid event
    API-->>S: Redirect to thank-you page
```

Per ADR-004, SCP never stores PAN/CVV. Payment state transitions require webhook verification — never client-side confirmation alone.

### 4.3 Cross-Module Event Flow

```mermaid
flowchart LR
    O[Orders Module] -->|OrderPlaced| EB[Event Bus]
    EB --> P[Payments]
    EB --> I[Inventory]
    EB --> N[Notifications]
    EB --> W[Webhooks]
    EB --> A[Analytics]
    EB --> AI[AI Platform]
```

Events are the **only** approved mechanism for cross-module side effects (FR-024).

---

## 5. Deployment Context

| Attribute | Phase 1 Value |
|-----------|---------------|
| Primary region | Nigeria (Lagos) or nearest West Africa cloud AZ |
| Kenya merchants | Route to East Africa region when activated (ADR-011) |
| Runtime | Docker Compose → managed VPS; FrankenPHP Octane |
| Edge | Cloudflare proxy (orange-cloud) on all public domains |
| Availability target | 99.9% monthly (NFR-021) |

Detailed topology in [Chapter 12](./12-deployment-and-runtime-topology.md).

---

## 6. Architecture Impact Summary

| Decision | Impact |
|----------|--------|
| Modular monolith | Single deploy; ACID cross-domain transactions |
| API-first | Storefront, admin, mobile, AI all consume same APIs |
| Event-driven | Loose coupling; async side effects; extraction-ready |
| Shared DB + RLS | Operational simplicity; defense-in-depth isolation |
| Nigeria-primary | NDPA alignment; low latency for Lagos merchants |

---

## 7. Acceptance Criteria

- [ ] C4 Context diagram includes all Phase 1 external systems (Paystack, Flutterwave, Cloudflare, M-Pesa)
- [ ] C4 Container diagram shows modular monolith boundary and externalized Meilisearch
- [ ] Checkout flow documented as PSP redirect (ADR-004)
- [ ] Primary data residency noted as Nigeria (ADR-011)
- [ ] Event-driven cross-module rule stated (FR-024)
- [ ] Architecture review confirms alignment with Volume 1 domain map

---

## References

- [ADR-001: Modular Monolith](../00-meta/adr/001-modular-monolith-over-microservices.md)
- [ADR-023: Platform OS](../00-meta/adr/023-sapphital-platform-os.md)
- [Chapter 13 — Platform OS Architecture](./13-platform-os-architecture.md)
- [ADR-004: Checkout / SAQ A](../00-meta/adr/004-checkout-psp-redirect-saq-a.md)
- [ADR-008: Cloudflare Edge](../00-meta/adr/008-edge-security-cloudflare.md)
- [ADR-011: Data Residency](../00-meta/adr/011-data-residency-africa.md)
- [Volume 1 — Domain Model](../01-vision/10-domain-model-overview.md)
- C4 Model: https://c4model.com/
