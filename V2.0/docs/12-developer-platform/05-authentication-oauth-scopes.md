# Chapter 05: Authentication, OAuth & Scopes

**Document ID:** SCP-DEV-001-05  
**Version:** 1.0.0  
**Status:** 📝 Draft  
**Traceability:** ADR-006, NFR-029, NFR-033, NFR-036, NFR-040, NFR-083  

---

## 1. Purpose

Define how developers authenticate to SCP APIs — from Phase 1 personal access tokens through Phase 3 OAuth 2.1 app installs — and how **scopes** enforce least-privilege access with NDPA-aligned data minimization.

## 2. Scope

- Personal access tokens (Sanctum)
- OAuth 2.1 authorization code + PKCE (app marketplace)
- Scope catalog and enforcement
- Token lifecycle, rotation, and revocation
- Tenant binding and test/live mode

## 3. Out of Scope

- Customer session auth (Storefront browser) — Volume 3
- Platform admin authentication — ADR-006
- mTLS enterprise auth (Phase 4 enterprise addendum)

## 4. Authentication Phases

| Phase | Mechanism | Use Case |
|-------|-----------|----------|
| **Phase 1** | Sanctum personal access tokens | Merchant scripts, agency integrations |
| **Phase 2** | Scoped service tokens (machine-to-machine) | Server-side cron jobs |
| **Phase 3** | OAuth 2.1 + PKCE | Marketplace apps (multi-merchant install) |

Per ADR-006: OAuth deferred until app ecosystem exists; Sanctum sufficient for Phase 1.

## 5. Personal Access Tokens

### 5.1 Token Format

```text
scp_{mode}_{random}
```

| Prefix | Mode | Example |
|--------|------|---------|
| `scp_live_` | Production | `scp_live_4f8d...` |
| `scp_test_` | Sandbox | `scp_test_9k2m...` |

Prefixes enable secret scanning (GitHub, GitLab, `gitleaks`) per ADR-006.

### 5.2 Creating Tokens

Merchant admin UI or API:

```http
POST /v1/api-tokens
Authorization: Bearer scp_live_... (admin session or existing token with write_tokens)
{
  "name": "ERP Sync — Lagos Office",
  "scopes": ["read_orders", "read_products", "read_customers"],
  "expires_at": "2027-07-12T00:00:00Z"
}
```

Response (token shown once):

```json
{
  "id": "tok_8mK2nP9",
  "object": "api_token",
  "name": "ERP Sync — Lagos Office",
  "scopes": ["read_orders", "read_products", "read_customers"],
  "token": "scp_live_4f8d9e2a1b3c5d7e8f0a2b4c6d8e0f2",
  "token_prefix": "scp_live_4f8d",
  "expires_at": "2027-07-12T00:00:00Z",
  "created_at": "2026-07-12T09:00:00Z"
}
```

### 5.3 Token Usage

```http
GET /v1/orders
Authorization: Bearer scp_live_4f8d9e2a1b3c5d7e8f0a2b4c6d8e0f2
```

## 6. OAuth 2.1 (Phase 3 — App Marketplace)

### 6.1 Authorization Flow

```mermaid
sequenceDiagram
    participant M as Merchant Admin
    participant S as SCP Admin
    participant A as Partner App
    participant API as SCP OAuth Server

    M->>S: Click "Install App"
    S->>API: GET /oauth/authorize?client_id=...&scope=...&code_challenge=...
    API->>M: Consent screen (scopes listed)
    M->>API: Approve
    API->>A: Redirect with authorization code
    A->>API: POST /oauth/token (code + code_verifier)
    API->>A: access_token + refresh_token
    A->>API: API calls with access_token
```

### 6.2 OAuth Endpoints

| Endpoint | Method | Purpose |
|----------|--------|---------|
| `/oauth/authorize` | GET | Authorization code request |
| `/oauth/token` | POST | Token exchange and refresh |
| `/oauth/revoke` | POST | Token revocation |
| `/.well-known/oauth-authorization-server` | GET | OAuth metadata (RFC 8414) |

### 6.3 PKCE Requirements

- `code_challenge_method`: `S256` only (OAuth 2.1)
- `code_verifier`: 43–128 character random string
- Public clients (SPA, mobile) must use PKCE; confidential clients recommended

### 6.4 App Credentials

| Credential | Format | Storage |
|------------|--------|---------|
| Client ID | `app_8xK9mN2p` | Public |
| Client Secret | `scs_...` | Encrypted; rotate annually |

## 7. Scope Catalog

### 7.1 Read Scopes

| Scope | Grants Access To |
|-------|------------------|
| `read_products` | Products, variants, collections, inventory levels |
| `read_orders` | Orders, fulfillments, refunds, draft orders |
| `read_customers` | Customers, addresses, segments (PII — NDPA) |
| `read_inventory` | Inventory levels, locations |
| `read_shipping` | Shipping zones, rates |
| `read_content` | Pages, blogs, menus |
| `read_themes` | Themes, assets (read-only) |
| `read_vendors` | Vendors, commissions |
| `read_payouts` | Vendor payouts |
| `read_webhooks` | Webhook endpoints, deliveries |
| `read_analytics` | Reports, dashboards |

### 7.2 Write Scopes

| Scope | Grants Access To |
|-------|------------------|
| `write_products` | Create/update/delete products, collections |
| `write_orders` | Create/update orders, refunds |
| `write_fulfillments` | Create fulfillments, tracking |
| `write_customers` | Create/update customers (PII — NDPA) |
| `write_inventory` | Adjust inventory levels |
| `write_discounts` | Discounts, gift cards |
| `write_shipping` | Shipping zones, rates |
| `write_content` | Pages, blogs, menus |
| `write_themes` | Theme upload, publish |
| `write_vendors` | Vendor management |
| `write_webhooks` | Webhook endpoint CRUD |
| `write_apps` | App configuration (self) |
| `write_tokens` | API token management (merchant admin) |

### 7.3 Scope Rules

1. **Write implies read** on the same resource domain.
2. Scopes are immutable after token creation; create new token to change.
3. Apps request scopes at install; merchant sees human-readable descriptions.
4. `read_customers` and `write_customers` require NDPA justification in app review (Chapter 10).
5. Platform-only scopes (`write_tokens`, `admin:*`) never granted to third-party apps.

## 8. Consent Screen (OAuth)

Displayed in merchant admin during app install:

```text
"Inventory Sync Pro" requests access to your store:

  ✓ Read products and inventory
  ✓ Read and update orders
  ✗ Read customers (not requested)

This app will process order data in accordance with SCP's
partner terms. [View privacy policy]

[Cancel]  [Install App]
```

Nigeria NDPA: consent must be **freely given, specific, and informed** (NFR-083). Apps accessing PII must link to their privacy policy and DPO contact.

## 9. Tenant Binding

Every token (personal or OAuth) is bound to:

| Attribute | Source |
|-----------|--------|
| `tenant_id` | Merchant store at token creation / app install |
| `mode` | `live` or `test` from token prefix |
| `actor_type` | `merchant_user`, `app`, `service` |
| `actor_id` | User ID or app ID |

Middleware stack:

```text
Request → ResolveBearerToken → LoadTokenMetadata → BindTenantContext
        → EnforceScopes → EnforceRLS → Controller
```

Cross-tenant token use returns `401 authentication_error` (invalid for target host).

## 10. Token Lifecycle

| Event | Action | Audit |
|-------|--------|-------|
| Created | Hash stored (Argon2id); plaintext shown once | `api_token.created` |
| Used | Last-used timestamp updated; anomaly detection | — |
| Rotated | Old token revoked; new issued | `api_token.rotated` |
| Expired | Auto-revoked; 401 on use | `api_token.expired` |
| Revoked | Immediate; 401 on use | `api_token.revoked` |

**Maximum token lifetime:** 1 year (personal access); OAuth access tokens: 1 hour with refresh.

## 11. Security Considerations

| Threat | Control |
|--------|---------|
| Token theft | Short-lived OAuth tokens; IP allowlist (enterprise); anomaly alerts |
| Scope escalation | Immutable scopes; app re-install required for new scopes |
| CSRF on OAuth | PKCE mandatory; `state` parameter required |
| Token in URL | Tokens only in `Authorization` header; never query params |
| Cross-tenant | Tenant binding + RLS (NFR-040) |
| PII over-collection | Scope minimum enforced; webhook payload masking |

## 12. Nigeria-Specific Notes

- **Agency model:** Agencies create tokens per client store (not cross-tenant); partner dashboard lists all managed stores
- **Low-trust environments:** Recommend IP allowlisting for production tokens
- **Phone OTP apps:** Customer auth scopes separate from admin API; no `read_customers` on storefront public endpoints
- **NDPA audit:** Token creation and PII scope grants logged to immutable audit trail (ADR-009)

## 13. Acceptance Criteria

| ID | Criterion | Verification |
|----|-----------|--------------|
| AC-DEV-05-01 | Personal access token authenticates Admin API | Integration test |
| AC-DEV-05-02 | Missing scope returns 403 `permission_error` | Scope matrix test |
| AC-DEV-05-03 | OAuth PKCE flow completes for test app | E2E test |
| AC-DEV-05-04 | Revoked token returns 401 immediately | Integration test |
| AC-DEV-05-05 | `scp_test_` token cannot access live data | Mode isolation test |
| AC-DEV-05-06 | PII scope grant creates audit log entry | Audit test |

## 14. References

- ADR-006: [Authentication Stack](../00-meta/adr/006-authentication-stack.md)
- OAuth 2.1 (RFC 9700): https://datatracker.ietf.org/doc/html/rfc9700
- PKCE (RFC 7636): https://datatracker.ietf.org/doc/html/rfc7636
- Shopify access scopes: https://shopify.dev/docs/api/usage/access-scopes
