# Chapter 04: Security Architecture

**Document ID:** SCP-SEC-001-04  
**Version:** 1.0.0  
**Status:** 📝 Draft  
**Traceability:** ADR-004 – ADR-011, NFR-029 – NFR-046  

---

## 1. Layered Defense Model

```text
Layer 0  Edge (Cloudflare — ADR-008)
         TLS 1.3, WAF, bot management, Turnstile, per-IP rate limits

Layer 1  Application gateway
         Host/tenant resolution, security headers, request size limits

Layer 2  AuthN / AuthZ (ADR-006)
         Session or token → policy check → tenant binding

Layer 3  Domain modules
         Validation, business rules, domain events

Layer 4  Data (ADR-002, ADR-005)
         Eloquent scopes → PostgreSQL RLS (SET LOCAL) → encryption at rest

Layer 5  Observability (ADR-009)
         Audit log, metrics, alerting, incident response
```

## 2. Authentication & Authorization

See [ADR-006](../00-meta/adr/006-authentication-stack.md).

**Authorization rules:**

- Deny-by-default Laravel policies on every model
- **Two independent checks:** permission AND tenant membership
- Platform admin: separate guard, MFA, short sessions
- Payout/bank detail changes: owner role + MFA step-up + audit

## 3. Tenant Isolation

Defense-in-depth stack:

1. Tenant resolution middleware (subdomain, custom domain, API token)
2. Global Eloquent `tenant_id` scope
3. PostgreSQL RLS with `FORCE ROW LEVEL SECURITY`
4. `SET LOCAL app.tenant_id` per transaction (ADR-005)
5. Tenant-prefixed cache keys, queue payloads, Meilisearch indexes
6. Automated isolation test suite — **blocking CI gate** (NFR-040)

## 4. Encryption & Secrets

| Layer | Standard | ADR |
|-------|----------|-----|
| Transit | TLS 1.3 | NFR-030 |
| At rest (DB/storage) | AES-256 | NFR-031 |
| Passwords | Argon2id | NFR-032, ADR-006 |
| PII columns | Laravel encrypted casts | Volume 11 |
| Secrets | Encrypted env → Vault Phase 2 | ADR-007 |

## 5. Edge Protection (Nigeria/Africa Latency)

Cloudflare provides WAF, DDoS, and African PoPs. Rate limiting:

| Tier | Limit | Scope |
|------|-------|-------|
| General browsing | 300 req/min | Per IP (edge) |
| Auth endpoints | 10 req/min | Per IP (edge) |
| API | Plan-based | Per tenant + per token (Redis) |
| Checkout | Strict + Turnstile | Per IP + session |

## 6. Web Application Controls

| Control | Implementation |
|---------|----------------|
| CSP | Nonce-based `script-src`; checkout pages allowlist PSP origins only (PCI + XSS) |
| CSRF | Laravel tokens on browser routes; API token auth exempt |
| XSS | Auto-escape; HTMLPurifier for CMS; lint ban on unescaped output |
| SQLi | Eloquent/parameterized only; RLS limits blast radius |

## 7. Audit Logging

See [ADR-009](../00-meta/adr/009-audit-log-immutability.md).

**Mandatory events:** authn (success/fail), authz denials, role changes, payout changes, refunds, exports, tenant lifecycle, impersonation, API token lifecycle.

**Regulatory alignment:** Audit trail supports NDPC CAR, ODPC audits, and PCI SAQ A admin access controls.

## 8. Per-Module Security Checklist (Summary)

Every module spec must include baseline items plus module extras:

| Module | Critical extras |
|--------|-----------------|
| Commerce | Server-side pricing; webhook HMAC; payment state machine |
| Theme Engine | No arbitrary code; checkout script lockdown |
| CMS | SSRF allowlist on oEmbed; upload validation |
| Marketplace | Cross-vendor PII walls; seller KYC encryption |
| AI Platform | Prompt injection defense; no cross-tenant context; DPIA |
| Developer Platform | OAuth scopes; webhook SSRF prevention |

Full checklist in module volumes; baseline enforced in PR template.

## 9. Admin Impersonation

See [ADR-010](../00-meta/adr/010-admin-impersonation.md). Required for support at scale in Nigeria market; strictly audited.

## 10. Data Residency

See [ADR-011](../00-meta/adr/011-data-residency-africa.md). Nigeria (Lagos) primary; Kenya region for East Africa merchants.

---

## References

- OWASP Cheat Sheet Series: https://cheatsheetseries.owasp.org/
- Stripe webhook security: https://docs.stripe.com/webhooks
- Paystack webhook verification: https://paystack.com/docs/payments/webhooks/
