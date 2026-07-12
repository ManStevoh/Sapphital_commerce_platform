# Chapter 02: API Design Standards

**Document ID:** SCP-DEV-001-02  
**Version:** 1.0.0  
**Status:** 📝 Draft  
**Traceability:** NFR-003, NFR-004, NFR-037, PRD-009  

---

## 1. Purpose

Define mandatory design standards for all SCP public REST APIs. Every endpoint, schema, and error response must conform so that developers experience **predictable, Stripe-quality** interactions regardless of resource domain.

## 2. Scope

- URL structure and versioning
- OpenAPI 3.1 authoring rules
- Request/response envelope patterns
- Error object specification
- Pagination, filtering, sorting
- Idempotency and concurrency
- Money, dates, and localization (Nigeria-first)

## 3. Out of Scope

- Complete endpoint catalog (Chapter 03)
- Authentication headers (Chapter 05)
- Rate limit implementation (Chapter 11)

## 4. API Families

| API | Base URL | Audience | Auth |
|-----|----------|----------|------|
| **Admin API** | `https://api.sapphital.com/v1` | Merchants, apps, agencies | Bearer token / OAuth |
| **Storefront API** | `https://{store}.sapphital.com/api/v1` | Headless storefronts, mobile | Public + session/customer token |
| **Platform API** | `https://api.sapphital.com/platform/v1` | SCP partners only | mTLS + platform key |

## 5. URL Design

### 5.1 Resource Naming

- Plural nouns, kebab-case: `/products`, `/order-fulfillments`
- IDs in path: `/products/{product_id}`
- Sub-resources: `/orders/{order_id}/line-items`
- Actions as verbs on sub-path (avoid when possible): `POST /orders/{id}/cancel`

### 5.2 Versioning

- Major version in URL path: `/v1/`
- Minor changes are backward-compatible within `/v1/`
- Breaking changes → `/v2/` with 12-month overlap
- `Sunset` and `Deprecation` headers on deprecated endpoints

```http
Deprecation: true
Sunset: Sat, 01 Jul 2028 00:00:00 GMT
Link: <https://developers.sapphital.com/changelog/2027-07-01>; rel="deprecation"
```

## 6. OpenAPI 3.1 Standards

### 6.1 File Organization

```text
openapi/
├── admin-api-v1.openapi.yaml      # Primary merchant API
├── storefront-api-v1.openapi.yaml # Headless commerce
├── components/
│   ├── schemas/                   # Shared objects (Money, Address, Error)
│   ├── parameters/                # Reusable query/path params
│   └── responses/                 # Standard error responses
└── spectral-ruleset.yaml          # Lint rules (CI gate)
```

### 6.2 Required OpenAPI Metadata

```yaml
openapi: 3.1.0
info:
  title: SCP Admin API
  version: 1.0.0
  description: SAPPHITAL Commerce Platform Admin REST API
  contact:
    name: SCP API Support
    url: https://developers.sapphital.com/support
    email: api-support@sapphital.com
  license:
    name: Proprietary
servers:
  - url: https://api.sapphital.com/v1
    description: Production
  - url: https://api.sandbox.sapphital.com/v1
    description: Sandbox (test mode)
jsonSchemaDialect: https://json-schema.org/draft/2020-12/schema
```

### 6.3 Schema Conventions

| Rule | Standard |
|------|----------|
| Object keys | `snake_case` |
| IDs | Prefixed strings: `prod_`, `ord_`, `cus_`, `whk_` |
| Timestamps | ISO 8601 UTC: `2026-07-12T09:30:00Z` |
| Money | Integer minor units + currency code |
| Enums | `SCREAMING_SNAKE` string values |
| Nullable | Explicit `nullable: true`; avoid absent vs null ambiguity |
| Expandable fields | `expand[]` query param (Stripe pattern) |

### 6.4 Money Object (Nigeria-First)

```yaml
Money:
  type: object
  required: [amount, currency]
  properties:
    amount:
      type: integer
      description: Amount in minor units (kobo for NGN, cents for USD)
      example: 150000
    currency:
      type: string
      pattern: '^[A-Z]{3}$'
      example: NGN
```

**Business rule:** All monetary amounts are integers in minor units. Never use floats. Default currency for Nigeria merchants is `NGN`.

### 6.5 Spectral CI Rules (Minimum)

- Every operation has `operationId`, `summary`, `description`
- Every operation documents `4xx` and `5xx` responses
- No inline anonymous objects deeper than 2 levels
- `example` or `examples` on all request body schemas
- Security scheme declared on all non-health endpoints

## 7. Request Standards

### 7.1 Headers

| Header | Required | Description |
|--------|----------|-------------|
| `Authorization` | Yes (Admin API) | `Bearer scp_live_...` or `Bearer scp_test_...` |
| `Content-Type` | Yes (writes) | `application/json` |
| `Accept` | Recommended | `application/json` |
| `Idempotency-Key` | Writes (optional) | UUID v4; 24-hour dedup window |
| `X-Request-Id` | Optional | Client trace ID; echoed in response |
| `SCP-Version` | Optional | Pin to dated version: `2026-07-12` |

### 7.2 Idempotency

All `POST` endpoints that create resources support `Idempotency-Key`:

```http
POST /v1/orders
Idempotency-Key: 7c9e6679-7425-40de-944b-e07fc1f90ae7
```

- Same key + same body → return original response (200/201)
- Same key + different body → `409 Conflict`
- Keys scoped per token; expire after 24 hours

## 8. Response Standards

### 8.1 Success Envelope

Single resource:

```json
{
  "id": "prod_8x9k2m",
  "object": "product",
  "name": "Ankara Print Dress",
  "price": { "amount": 2500000, "currency": "NGN" },
  "created_at": "2026-07-12T09:30:00Z",
  "updated_at": "2026-07-12T09:30:00Z"
}
```

List resources:

```json
{
  "object": "list",
  "data": [ ... ],
  "has_more": true,
  "next_cursor": "eyJpZCI6InByb2RfOHg5azJtIn0"
}
```

### 8.2 Error Envelope

```json
{
  "error": {
    "type": "invalid_request_error",
    "code": "parameter_missing",
    "message": "Missing required parameter: name",
    "param": "name",
    "doc_url": "https://developers.sapphital.com/errors/parameter_missing",
    "request_id": "req_3Nx8kQm2vL9p"
  }
}
```

### 8.3 Error Types

| `type` | HTTP Status | When |
|--------|-------------|------|
| `invalid_request_error` | 400 | Malformed request, missing param |
| `authentication_error` | 401 | Missing/invalid token |
| `permission_error` | 403 | Valid token, insufficient scope |
| `not_found_error` | 404 | Resource not found or cross-tenant |
| `conflict_error` | 409 | Idempotency conflict, state conflict |
| `rate_limit_error` | 429 | Rate limit exceeded |
| `api_error` | 500 | Internal error (retryable) |

**Security rule:** `404` returned for cross-tenant resource access (not `403`) to prevent enumeration.

### 8.4 Rate Limit Headers

```http
X-RateLimit-Limit: 2000
X-RateLimit-Remaining: 1997
X-RateLimit-Reset: 1720771200
Retry-After: 42
```

## 9. Pagination

Cursor-based pagination (not offset) for consistency at scale:

```http
GET /v1/products?limit=25&cursor=eyJpZCI6InByb2RfOHg5azJtIn0
```

| Parameter | Default | Max |
|-----------|---------|-----|
| `limit` | 25 | 100 |

## 10. Filtering and Sorting

```http
GET /v1/orders?status=PAID&created_at[gte]=2026-07-01&sort=-created_at
```

| Operator | Syntax | Example |
|----------|--------|---------|
| Equals | `field=value` | `status=PAID` |
| Greater than | `field[gte]=` | `created_at[gte]=2026-07-01` |
| Less than | `field[lte]=` | `total[lte]=500000` |
| In | `field[in]=a,b` | `status[in]=PAID,SHIPPED` |

## 11. Partial Updates

- `PATCH` with JSON Merge Patch (`application/merge-patch+json`)
- `PUT` for full replacement (rare; document per resource)
- Read-only fields (`id`, `created_at`) ignored on write

## 12. Architecture Impact

- Laravel API controllers are thin; validation via Form Requests generated from OpenAPI
- `scp openapi:generate` produces PHP request DTOs and TypeScript types
- Contract tests verify implementation matches spec (Schemathesis in CI)
- API gateway middleware enforces version, rate limits, and auth before routing

## 13. Nigeria-Specific API Notes

| Topic | Standard |
|-------|----------|
| Phone numbers | E.164 format: `+2348012345678` |
| Addresses | `state` required (Nigeria LGAs in reference data endpoint) |
| Tax | `tax_lines[]` with `type: VAT` at 7.5% default |
| Payments | `payment_method: PAYSTACK` \| `FLUTTERWAVE` \| `BANK_TRANSFER` |
| BVN/NIN | Never exposed via API; KYC status only: `kyc_status: VERIFIED` |

## 14. Test Strategy

- Spectral lint on every OpenAPI change (CI blocking)
- Schemathesis property-based tests against sandbox
- Golden-file response snapshots per endpoint
- Backward-compat diff tool on PR (oasdiff)

## 15. Acceptance Criteria

| ID | Criterion | Verification |
|----|-----------|--------------|
| AC-DEV-02-01 | Admin API OpenAPI 3.1 passes Spectral with zero errors | CI |
| AC-DEV-02-02 | All error responses match error envelope schema | Contract test |
| AC-DEV-02-03 | Money fields are integer minor units across all schemas | Schema audit |
| AC-DEV-02-04 | Cursor pagination on all list endpoints | Integration test |
| AC-DEV-02-05 | Idempotency-Key deduplication works for POST creates | Integration test |
| AC-DEV-02-06 | Deprecation headers present 90 days before removal | Manual audit |

## 16. References

- OpenAPI 3.1: https://spec.openapis.org/oas/v3.1.0
- Stripe API conventions: https://docs.stripe.com/api
- JSON Merge Patch (RFC 7396): https://datatracker.ietf.org/doc/html/rfc7396
- Idempotency (Stripe): https://docs.stripe.com/api/idempotent_requests
