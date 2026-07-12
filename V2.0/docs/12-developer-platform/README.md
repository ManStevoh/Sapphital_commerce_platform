# Volume 12: Developer Platform

**Document ID:** SCP-DEV-001  
**Version:** 1.0.0  
**Status:** ✅ Active  
**Depends On:** Volume 3 (Architecture), Volume 5 (Commerce), Volume 6 (Theme Engine), Volume 11 (Security), ADR-001, ADR-002, ADR-006  
**Owner:** Sapphital Learning Company  

---

## Purpose

This volume defines SCP's **developer platform** — the APIs, webhooks, SDKs, OAuth model, plugin runtime, theme app extensions, CLI, and app marketplace that enable third-party developers, agencies, and enterprise integrators to build on SCP without forking core.

The quality bar is **Stripe-grade API design** and **Shopify-grade extensibility**, adapted for Nigeria-first commerce realities: intermittent connectivity, mobile-first developers, Paystack/Flutterwave integration patterns, and NDPA-compliant data handling.

## Scope

- REST Admin API and Storefront API (OpenAPI 3.1)
- Webhook event system and delivery guarantees
- Authentication (Sanctum tokens → OAuth 2.1 + PKCE for apps)
- Official SDKs (PHP/Composer, JavaScript/TypeScript)
- Plugin runtime and hook system
- Theme app extensions (cross-ref Volume 6)
- `scp` CLI for scaffolding, deployment, and local development
- App review and marketplace listing requirements
- Developer security (SSRF, rate limits, scope enforcement)

## Out of Scope

- GraphQL Storefront API (Phase 3 roadmap — see [Chapter 01](./01-developer-platform-overview.md))
- Internal service-to-service APIs (Volume 3)
- Payment provider webhook ingestion from PSPs (Volume 5)
- Infrastructure deployment runbooks (Volume 10)

## Chapters

| # | Chapter | Status |
|---|---------|--------|
| 01 | [Developer Platform Overview](./01-developer-platform-overview.md) | ✅ Active |
| 02 | [API Design Standards](./02-api-design-standards.md) | ✅ Active |
| 03 | [REST API Specification](./03-rest-api-specification.md) | ✅ Active |
| 04 | [Webhooks and Events](./04-webhooks-and-events.md) | ✅ Active |
| 05 | [Authentication, OAuth & Scopes](./05-authentication-oauth-scopes.md) | ✅ Active |
| 06 | [SDKs — PHP & JavaScript](./06-sdks-php-js.md) | ✅ Active |
| 07 | [Plugin Runtime](./07-plugin-runtime.md) | ✅ Active |
| 08 | [Theme App Extensions](./08-theme-app-extensions.md) | ✅ Active |
| 09 | [CLI — `scp` Tool](./09-cli-scp-tool.md) | ✅ Active |
| 10 | [App Review & Marketplace](./10-app-review-marketplace.md) | ✅ Active |
| 11 | [Security — SSRF & Rate Limits](./11-security-ssrf-rate-limits.md) | ✅ Active |

## Traceability

| Requirement | Volume 12 Coverage |
|-------------|-------------------|
| PRD-009 | Comprehensive developer APIs and SDKs |
| PRD-006 | Theme marketplace developer support |
| NFR-003, NFR-004 | API latency targets |
| NFR-017, NFR-020 | API and webhook scale |
| NFR-036 | Rate limiting |
| NFR-040 | Tenant isolation in all API paths |
| NFR-071, NFR-083 | Nigeria data residency and NDPA |
| ADR-006 | Authentication stack evolution |

## Nigeria Developer Ecosystem Context

| Factor | SCP Response |
|--------|--------------|
| Mobile-first developers | SDKs optimized for low-bandwidth; CLI works over SSH |
| Agency-led integrations | Partner tier, sandbox tenants, white-label docs |
| Local payment rails | Webhook events for Paystack/Flutterwave order states |
| Developer communities | Lagos/Abuja/Port Harcourt meetups; WhatsApp dev channel |
| University CS programs | Free sandbox tier; hackathon API keys |
| Connectivity variance | Webhook retries with exponential backoff; idempotent APIs |

## Related Volumes

- [Volume 3 — Architecture](../03-architecture/README.md) — Modular monolith, event bus
- [Volume 5 — Commerce Engine](../05-commerce-engine/README.md) — Order/payment domain events
- [Volume 6 — Theme Engine](../06-theme-engine/README.md) — Theme SDK, sections, blocks
- [Volume 11 — Security](../11-security/README.md) — OWASP ASVS, tenant isolation

## Standards Baseline (July 2026)

| Standard | Version | SCP Target |
|----------|---------|------------|
| OpenAPI | **3.1.0** | All public REST APIs |
| OAuth | **2.1** (RFC 9700) | App marketplace (Phase 3) |
| JSON:API | — | Not adopted; SCP uses Stripe-style flat JSON |
| Webhook signatures | HMAC-SHA256 | Stripe-compatible header pattern |
| Semantic versioning | SemVer 2.0 | API versions, SDKs, CLI |
