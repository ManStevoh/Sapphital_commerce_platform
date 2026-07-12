# Chapter 01: Developer Platform Overview

**Document ID:** SCP-DEV-001-01  
**Version:** 1.0.0  
**Status:** 📝 Draft  
**Traceability:** PRD-009, PRD-006, NFR-003 – NFR-004, NFR-017, NFR-020, ADR-001, ADR-006  

---

## 1. Purpose

The SCP Developer Platform enables third parties to extend commerce capabilities — integrations, automation, custom storefronts, and marketplace apps — without modifying SCP core. This chapter establishes platform identity, developer personas, phased rollout, and architectural boundaries.

## 2. Scope

- Developer personas and use cases
- Platform surfaces (Admin API, Storefront API, webhooks, plugins, themes, CLI)
- Phased capability roadmap
- Nigeria-first developer ecosystem strategy
- Architecture overview and module boundaries

## 3. Out of Scope

- Detailed API endpoint catalogs (Chapter 03)
- OAuth implementation details (Chapter 05)
- Plugin sandbox internals (Chapter 07)

## 4. Platform Identity

SCP's developer platform follows the **Commerce Operating System** model:

```text
Merchants operate stores → Developers extend the OS → Partners distribute solutions
```

Comparable platforms studied (E1 sources):

| Platform | Strength SCP Adopts | SCP Differentiation |
|----------|---------------------|---------------------|
| **Stripe** | API design, idempotency, error objects, SDK quality | Commerce domain depth; Africa payment events |
| **Shopify** | App model, OAuth install flow, theme extensions, CLI | Nigeria NDPA-native; lower bandwidth SDK defaults |
| **Paystack** | Nigeria developer familiarity, webhook HMAC | Full commerce + marketplace, not payments-only |

## 5. Developer Personas

| Persona | Location | Primary Need | SCP Surface |
|---------|----------|--------------|-------------|
| **Agency integrator** | Lagos, Nairobi | ERP/CRM sync, custom checkout | Admin API + webhooks |
| **SaaS app builder** | Remote / global | Multi-tenant app on marketplace | OAuth + Admin API |
| **Theme designer** | Nigeria, Ghana | Sell themes in Theme Store | Theme SDK + CLI |
| **Plugin author** | University / indie | Extend admin or storefront | Plugin runtime |
| **Enterprise IT** | Lagos enterprise | Private integrations, SLA | Enterprise API tier |
| **Merchant developer** | In-house | Custom storefront, mobile app | Storefront API + tokens |

## 6. Platform Surfaces

```mermaid
flowchart TB
    subgraph dev["Developer Tools"]
        CLI[scp CLI]
        SDK_PHP[PHP SDK]
        SDK_JS[JS/TS SDK]
        DOCS[Developer Portal]
    end

    subgraph runtime["SCP Runtime"]
        ADMIN[Admin REST API<br/>api.sapphital.com/v1]
        STOREFRONT[Storefront REST API<br/>{store}.sapphital.com/api/v1]
        WH[Webhook Dispatcher]
        PLUG[Plugin Runtime]
        THEME[Theme App Extensions]
    end

    subgraph consumers["Consumers"]
        APP[Partner Apps]
        AGENCY[Agency Scripts]
        MOBILE[Mobile / Headless]
        ERP[ERP / CRM]
    end

    CLI --> ADMIN
    SDK_PHP --> ADMIN
    SDK_JS --> STOREFRONT
    DOCS --> dev

    APP --> ADMIN
    AGENCY --> ADMIN
    MOBILE --> STOREFRONT
    ERP --> ADMIN

    ADMIN --> WH
    STOREFRONT --> WH
    PLUG --> ADMIN
    THEME --> STOREFRONT
```

### 6.1 Admin REST API

Merchant-scoped and app-scoped API for managing products, orders, customers, inventory, and settings. Authenticated via personal access tokens (Phase 1) or OAuth app tokens (Phase 3).

### 6.2 Storefront REST API

Public, rate-limited API for headless storefronts and mobile apps. Read-heavy; cart/checkout mutations require session or customer token.

### 6.3 Webhooks

Outbound HTTPS notifications for domain events (`order.paid`, `product.updated`, etc.). HMAC-SHA256 signed; Stripe-compatible verification pattern.

### 6.4 Plugin Runtime

Server-side PHP plugins extending admin workflows and reacting to domain hooks. Sandboxed; no direct database access outside declared scopes.

### 6.5 Theme App Extensions

Client-side and server-side extensions embedded in merchant themes (app blocks, app embeds). Cross-reference [Volume 6](../06-theme-engine/README.md).

### 6.6 `scp` CLI

Command-line tool for app scaffolding, theme development, webhook testing, and deployment packaging.

## 7. Phased Rollout

| Phase | Timeline | Capabilities |
|-------|----------|--------------|
| **Phase 1** | Launch | Admin API (core resources), Sanctum tokens, webhooks (commerce events), PHP SDK, developer docs portal |
| **Phase 2** | +6 months | Storefront API, JS SDK, `scp` CLI beta, sandbox tenants, webhook replay UI |
| **Phase 3** | +12 months | OAuth 2.1 app install, Plugin runtime GA, App Marketplace, theme app extensions |
| **Phase 4** | +18 months | GraphQL Storefront API, partner revenue share, enterprise dedicated endpoints |

Assumption: OAuth deferred to Phase 3 per ADR-006 — Sanctum tokens sufficient until app ecosystem exists.

## 8. Architecture Impact

The developer platform sits on the modular monolith (ADR-001) with these boundaries:

| Layer | Responsibility |
|-------|----------------|
| **API Gateway** | Version routing, rate limits, auth middleware, OpenAPI validation |
| **Domain modules** | Business logic; emit domain events |
| **Event bus** | Internal events → webhook dispatcher |
| **Developer module** | Apps, tokens, webhooks, plugin registry, marketplace metadata |
| **Tenant context** | Every API call resolves `tenant_id` before domain access (ADR-002) |

Developers never access PostgreSQL directly. All data flows through versioned HTTP APIs or declared plugin hooks.

## 9. Data Ownership

| Entity | Owner Module | Developer Access |
|--------|--------------|------------------|
| `ApiToken` | Developer | Create/revoke via merchant admin or OAuth |
| `WebhookEndpoint` | Developer | CRUD via Admin API |
| `WebhookDelivery` | Developer | Read-only; 30-day retention |
| `App` (marketplace) | Developer | Partner portal; platform review |
| `Plugin` | Developer | Installed per-tenant; manifest declares scopes |
| Merchant business data | Commerce/Marketplace | Read/write per token scope |

## 10. Nigeria Developer Ecosystem Strategy

### 10.1 Community & Distribution

- **Developer portal** at `developers.sapphital.com` (English; Pidgin/Hausa glossary Phase 2)
- **Lagos Developer Relations** — quarterly hackathons co-hosted with Paystack Developer Community, nHub, and CcHUB
- **WhatsApp Dev Channel** — webhook debugging tips, API changelog alerts (opt-in)
- **University partnerships** — UNILAG, ABU, FUTA CS departments; free sandbox for capstone projects

### 10.2 Technical Adaptations

| Challenge | SCP Design Response |
|-----------|---------------------|
| Intermittent 3G/4G | SDK request timeouts default 30s; retry with jitter; compressed responses |
| Shared hosting familiarity | PHP SDK first (Composer); aligns with Nigerian agency stack |
| Paystack/Flutterwave literacy | Webhook events mirror PSP state transitions; docs cross-link Volume 5 |
| NDPA data handling | Scopes enforce minimum data; audit log on all API exports (NFR-083) |
| Naira-first commerce | All money fields in minor units (kobo); `currency: "NGN"` default |

### 10.3 Partner Tiers

| Tier | Requirements | Benefits |
|------|--------------|----------|
| **Registered** | Verified email, ToS acceptance | Sandbox, docs, community |
| **Certified Partner** | 1 live integration, security review | Co-marketing, priority support |
| **Premier Partner** | 5+ merchant installs, SLA app | Revenue share, dedicated Slack |

## 11. Business Rules

1. Every API request must resolve a valid tenant before domain module execution.
2. Test mode (`scp_test_*` tokens) operates on sandbox data only; never mutates live orders.
3. Breaking API changes require a new major version; minimum 12-month deprecation window.
4. Apps accessing customer PII must declare `read_customers` scope and pass NDPA review.
5. Webhook endpoints must use HTTPS with valid certificates; HTTP rejected at registration.
6. Plugin code must pass static analysis and scope validation before marketplace listing.

## 12. Events

Internal domain events consumed by the webhook dispatcher (full catalog in Chapter 04):

| Event | Source Module | Webhook Topic |
|-------|---------------|---------------|
| `OrderPlaced` | Commerce | `order.created` |
| `OrderPaid` | Commerce | `order.paid` |
| `ProductUpdated` | Commerce | `product.updated` |
| `CustomerCreated` | CRM | `customer.created` |
| `AppInstalled` | Developer | `app.installed` |

## 13. Security Considerations

- Deny-by-default scope enforcement on every endpoint (Chapter 05)
- Webhook SSRF prevention — URL validation, IP blocklist, no private ranges (Chapter 11)
- Rate limits per tenant tier (Volume 1 tenant tiers)
- Token prefix scanning in CI (`scp_live_`, `scp_test_`) per ADR-006
- No API access to platform admin routes

## 14. Performance Targets

| Metric | Target | NFR |
|--------|--------|-----|
| Admin API read p95 | ≤ 200ms | NFR-003 |
| Admin API write p95 | ≤ 500ms | NFR-004 |
| Webhook dispatch latency (event → first attempt) | ≤ 5s p95 | NFR-008 |
| Webhook delivery success rate | ≥ 99.5% (after retries) | NFR-021 |
| Developer portal doc search | ≤ 300ms | NFR-005 |

## 15. Observability

- OpenTelemetry trace ID returned in `X-Request-Id` response header
- Per-app metrics: request count, error rate, webhook delivery rate
- Developer dashboard: API usage graphs, webhook delivery log, rate limit headers
- Audit events: token created/revoked, scope changed, app installed/uninstalled

## 16. Risks and Tradeoffs

| Risk | Mitigation |
|------|------------|
| OAuth complexity too early | Sanctum tokens Phase 1; OAuth when marketplace launches |
| Plugin sandbox escape | No `eval`; allowlisted PHP functions; isolated autoload |
| API surface creep | OpenAPI-first; new endpoints require architecture review |
| Webhook abuse (SSRF) | Strict URL validation; manual review for enterprise webhooks |
| Low documentation adoption | Stripe-quality examples; Nigeria-specific quickstarts |

## 17. Acceptance Criteria

| ID | Criterion | Verification |
|----|-----------|--------------|
| AC-DEV-01-01 | Developer portal publishes OpenAPI 3.1 spec for Admin API | Spec validates in Spectral CI |
| AC-DEV-01-02 | Sandbox tenant provisioned via self-service signup | E2E test |
| AC-DEV-01-03 | PHP SDK installs via Composer and lists products | Integration test |
| AC-DEV-01-04 | Webhook `order.paid` delivered with valid HMAC | Webhook test harness |
| AC-DEV-01-05 | Cross-tenant API access returns 403 | Isolation test suite (NFR-040) |
| AC-DEV-01-06 | Nigeria quickstart guide completes in < 30 minutes | UX review |

## 18. References

- Stripe API design: https://docs.stripe.com/api
- Shopify app development: https://shopify.dev/docs/apps/build
- Paystack webhooks: https://paystack.com/docs/payments/webhooks/
- OpenAPI 3.1: https://spec.openapis.org/oas/v3.1.0
- ADR-006: [Authentication Stack](../00-meta/adr/006-authentication-stack.md)
