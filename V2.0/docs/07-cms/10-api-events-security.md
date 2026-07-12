# Chapter 10: API, Events & Security

**Document ID:** SCP-CMS-001-10  
**Version:** 1.0.0  
**Status:** ✅ Active  
**Traceability:** FR-CMS-011, FR-CMS-012, ADR-002, ADR-008, NFR-040, NFR-036

---

## Purpose

Define **CMS Admin and Storefront APIs**, **domain events**, and **security controls** (SSRF, upload abuse, tenant isolation) for the Content and Learning bounded contexts.

## Scope

- REST API surface (Admin + Storefront)
- Event catalog and consumers
- Authorization matrix
- SSRF prevention for oEmbed and webhooks
- Rate limits
- Audit logging
- Webhook payloads for content changes

## Out of Scope

- Full OpenAPI file (Volume 12 Ch. 03)
- Platform-wide auth (Volume 3 Ch. 06)
- AI agent APIs (Volume 9)

---

## 1. API Overview

| API | Base Path | Auth | Audience |
|-----|-----------|------|----------|
| Admin CMS | `/admin/v1/content/*` | Sanctum + abilities | Merchant staff |
| Admin Learning | `/admin/v1/courses/*` | Sanctum + abilities | Merchant staff |
| Storefront Content | `/storefront/v1/pages/*` | Public / customer | Shoppers, students |
| Storefront Learning | `/storefront/v1/learn/*` | Customer session | Enrolled students |

All responses include `X-Tenant-Id` internally; never expose other tenants' IDs in errors.

---

## 2. Admin API — Content

| Resource | Methods | Abilities |
|----------|---------|-----------|
| `/pages` | CRUD | `content:read`, `content:write` |
| `/pages/{id}/publish` | POST | `content:publish` |
| `/pages/{id}/versions` | GET | `content:read` |
| `/blog/posts` | CRUD | `content:write` |
| `/blog/posts/{id}/publish` | POST | `content:publish` |
| `/content-types` | CRUD | `content:admin` |
| `/content-entries` | CRUD | `content:write` |
| `/navigation/{handle}` | GET/PUT | `content:write` |
| `/media` | CRUD | `content:write` |
| `/seo-profiles/{entity}` | PATCH | `content:write` |
| `/redirects` | CRUD | `content:admin` |
| `/releases` | CRUD | `content:publish` |

### 2.1 Pagination & Filtering

```json
{
  "data": [...],
  "meta": {
    "cursor": "eyJpZCI6...",
    "has_more": true
  }
}
```

Cursor-based pagination default; `limit` max 100.

---

## 3. Storefront API — Content

| Endpoint | Cache | Auth |
|----------|-------|------|
| `GET /pages/{slug}` | ISR 60s | Public |
| `GET /blog/posts` | ISR 60s | Public |
| `GET /blog/posts/{slug}` | ISR 60s | Public |
| `GET /navigation/{handle}` | ISR 300s | Public |
| `GET /sitemap.xml` | CDN 3600s | Public |
| `GET /learn/lessons/{slug}` | No cache | Customer + enrollment |

Published content only — drafts return 404 on storefront (not 403, to avoid enumeration).

---

## 4. Domain Events

| Event | Payload | Consumers |
|-------|---------|-----------|
| `PagePublished` | `page_id`, `slug`, `locale` | Search index, webhooks, ISR purge |
| `PageUnpublished` | `page_id` | ISR purge |
| `PostPublished` | `post_id`, `slug` | Sitemap, webhooks |
| `ContentReleaseExecuted` | `release_id`, `items[]` | Bulk ISR purge |
| `NavigationUpdated` | `handle` | ISR purge |
| `MediaReady` | `media_id` | — |
| `EnrollmentCreated` | `enrollment_id`, `course_id`, `customer_id` | Notifications, analytics |
| `LessonCompleted` | `enrollment_id`, `lesson_id` | Drip unlock, webhooks |
| `CourseCompleted` | `enrollment_id` | Certificate job |

Events emitted via Laravel event bus only — no direct cross-module DB writes (FR-024).

---

## 5. Webhooks (Merchant-Configured)

| Topic | Trigger |
|-------|---------|
| `content.page.published` | PagePublished |
| `content.post.published` | PostPublished |
| `learning.enrollment.created` | EnrollmentCreated |
| `learning.course.completed` | CourseCompleted |

Payload includes `tenant_id`, `occurred_at`, `data` object — HMAC signed (Volume 12 Ch. 04).

---

## 6. Authorization Matrix

| Role | Read | Write | Publish | Admin schema |
|------|------|-------|---------|--------------|
| Viewer | ✅ | ❌ | ❌ | ❌ |
| Editor | ✅ | ✅ | ❌ | ❌ |
| Publisher | ✅ | ✅ | ✅ | ❌ |
| Admin | ✅ | ✅ | ✅ | ✅ |

Platform admin impersonation: audit logged per ADR-010.

---

## 7. Security Controls

### 7.1 Tenant Isolation

| Layer | Control |
|-------|---------|
| HTTP | Tenant middleware from host/token |
| Application | `tenant_id` on every query |
| Database | RLS `app.tenant_id` (ADR-002) |
| Media | R2 prefix per tenant |
| Cache | Keys prefixed `t:{tenant_id}:` |

Isolation test suite covers all CMS models (Volume 13 Ch. 04).

### 7.2 SSRF Prevention

| Feature | Control |
|---------|---------|
| oEmbed fetch | Allowlist: YouTube, Vimeo, Loom, Cloudflare Stream |
| Media import URL | https only; same IP/DNS checks as oEmbed |
| Custom URL redirect | No private IP ranges; DNS rebinding check; max 3 redirects |
| Webhook delivery / test | Internal URLs blocked (Volume 12) |
| Preview iframe | Same-origin storefront only |

**Mandatory outbound checks:** resolve DNS → reject private/link-local/metadata IPs (`169.254.169.254`, cloud IMDS) → timeout ≤5s → size cap. Metrics + alerts on reject spikes.

Blocked IP ranges: `10.0.0.0/8`, `172.16.0.0/12`, `192.168.0.0/16`, `127.0.0.0/8`, `169.254.0.0/16`, `::1`, IPv6 ULA.

### 7.3 Upload Security

See Chapter 08. Phase 3 controls:

| Control | Rule |
|---------|------|
| MIME allowlist | jpeg, png, webp, gif, avif, mp4, webm, pdf (plan-gated) |
| Magic bytes | Must match declared MIME |
| Storage key | Server-generated `tenants/{tenant_id}/...` only |
| SVG | **Rejected** in Phase 3 (XSS) |
| Malware scan | Async quarantine before public CDN |
| Quotas | Plan storage + per-file caps (NFR-018) |

### 7.4 Rate Limits

| Endpoint Class | Limit |
|----------------|-------|
| Admin write | 120 req/min per user |
| Admin publish | 30 req/min |
| Storefront read | 300 req/min per IP (CDN absorbs most) |
| Media upload | 20 files/hour per tenant |
| Lesson complete | 60 req/min per customer |

Return `429` with `Retry-After`.

---

## 8. Audit Logging

| Action | Logged Fields |
|--------|---------------|
| Publish/unpublish | actor, entity, version, IP |
| Delete content | actor, entity, soft_delete |
| Redirect create | actor, from, to |
| Enrollment revoke | actor, reason |
| SEO change | actor, entity, diff hash |

Retention: 2 years (ADR-009).

---

## 9. Error Handling

| Code | Use |
|------|-----|
| 400 | Validation errors (field map) |
| 401 | Unauthenticated |
| 403 | Missing ability |
| 404 | Not found or draft on storefront |
| 409 | Slug conflict |
| 422 | Business rule (e.g., publish without alt text) |
| 429 | Rate limit |

Never return stack traces or other tenant IDs in errors.

---

## 10. Storefront GraphQL Additions (Phase 3)

```graphql
type Query {
  page(handle: String!, locale: String): Page
  article(blogHandle: String!, handle: String!): Post
  navigation(handle: String!, locale: String): Navigation
  course(handle: String!): Course
  myEnrollments: [Enrollment!]!
  verifyCertificate(code: String!): CertificatePublic
}
```

Locked lessons return `isLocked: true` and **omit** lesson body. Drafts are absent (equivalent to REST 404).

---

## 11. Isolation Test Matrix (CI Blocking)

| Resource | Assertion |
|----------|-----------|
| Page / Post / Entry | Tenant B cannot read Tenant A UUID |
| Media metadata + bytes | Cross-tenant signed URL fails |
| Navigation / Release token | Cross-tenant redeem fails |
| Course / Lesson / Enrollment | IDOR suite pass |
| Certificate verify | Public OK; no admin PII leak |

**Pass criterion:** 0 cross-tenant accesses (NFR-040).

---

## 12. Volume 7 Acceptance Criteria

### 12.1 CMS Core (Phase 3)

| ID | Criterion |
|----|-----------|
| AC-CMS-001 | Landing page ≥3 sections without code ≤10 min |
| AC-CMS-002 | Blog publish visible ≤60s p95 |
| AC-CMS-003 | Nav changes ≤30s |
| AC-CMS-004 | Media upload + WebP variants |
| AC-CMS-005 | Empty meta title blocks indexable publish |
| AC-CMS-006 | Sitemap includes published pages/posts |
| AC-CMS-007 | Slug change → 301 (default on) |
| AC-CMS-008 | Version restore → draft; live untouched |
| AC-CMS-009 | Scheduled publish ±60s |
| AC-CMS-010 | Tenant isolation suite pass |
| AC-CMS-011–018 | Releases, preview, locales, content types, saved sections, revision, autosave, SEO score (Ch. 01–07) |

### 12.2 Security Gates

| ID | Criterion |
|----|-----------|
| AC-CMS-SEC-001 | Unregistered section type rejected |
| AC-CMS-SEC-002 | SSRF suite 100% pass (IMDS, localhost, DNS rebinding) |
| AC-CMS-SEC-003 | Magic-byte mismatch rejected |
| AC-CMS-SEC-004 | SVG rejected (Phase 3) |
| AC-CMS-SEC-005 | XSS paste fixtures do not execute |
| AC-CMS-SEC-006 | Preview token expired/cross-tenant fails |
| AC-CMS-SEC-007 | Author without publish cannot publish via API |
| AC-CMS-SEC-008 | Outbound webhook URL validation blocks private IPs |

### 12.3 Education (Phase 3.5)

| ID | Criterion |
|----|-----------|
| AC-LRN-001–010 | Chapter 09 checklist (checkout → enroll → drip → certificate → JSON-LD) |
| AC-LRN-SEC-001 | Locked lesson API omits body |
| AC-LRN-SEC-002 | Enrollment IDOR suite pass |

### 12.4 Chapter 10 Documentation Gates

- [ ] Admin and Storefront API paths documented with abilities
- [ ] Event catalog ≥ 10 events with consumers listed
- [ ] Draft content returns 404 on storefront
- [ ] SSRF allowlist and private IP blocks documented
- [ ] Rate limits per endpoint class defined
- [ ] RLS + cache prefix isolation cross-referenced
- [ ] Webhook topics for content and learning defined
- [ ] Audit actions for publish, delete, redirect, revoke

**Sign-off:** Lead Architect, Security reviewer, Product (Academy).

---

## References

- OWASP SSRF Prevention Cheat Sheet; OWASP File Upload Cheat Sheet
- [Volume 3 Ch. 06 — Request Lifecycle](../03-architecture/06-request-lifecycle-and-auth.md)
- [Volume 12 Ch. 04 — Webhooks](../12-developer-platform/04-webhooks-and-events.md)
- [Volume 11 Ch. 03 — Threat Model](../11-security/03-threat-model.md)
- [Volume 13 Ch. 04 — Tenant Isolation](../13-testing/04-tenant-isolation-test-suite.md)
- ADR-002, ADR-003, ADR-005, ADR-008, ADR-009
