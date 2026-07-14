# Chapter 07: Phase 2 — Growth Playbook

**Document ID:** SCP-IMP-021-07  
**Version:** 1.0.0  
**Status:** ✅ Active  
**Traceability:** Volume 7, Volume 9, Volume 15 H2, PRD-006 – PRD-012  

---

## Purpose

Step-by-step build sequence for **Phase 2 Growth** — CMS and page builder, AI platform v1, search and merchandising maturity, infrastructure scale to 5,000 merchants, and operational excellence — bridging Nigeria GA to platform marketplace readiness.

## Scope

- CMS, blog, and landing page builder
- AI catalog assist and support suggestions v1
- Advanced commerce features (digital products, subscriptions)
- Infrastructure Phase 2 topology
- Operations maturity (on-call, status page, runbooks)
- Additional built-in themes (Savanna, Terminal)

## Out of Scope

- Multi-vendor marketplace (Chapter 08)
- OAuth developer apps (Chapter 08)
- POS and mobile native apps (Volume 15 H4)

## Prerequisites

- [ ] Chapter 12 Nigeria GA launch complete
- [ ] Phase 1 SLOs sustained for 30 days (99.9% availability)
- [ ] ≥ 100 active paying merchants (validates product-market fit signal)
- [ ] OWASP ASVS L2 ≥ 95% verified

---

## §1 CMS & Page Builder (Weeks 21–28)

Build per [Volume 7](../07-cms/README.md) and [ADR-012](../00-meta/adr/012-hybrid-cms-theme-sections-content-types.md):

### 1.1 Content Model

**Checklist:**

- [x] `Page` entity: `title`, `slug`, `body_json`, `seo_title`, `seo_description`, `status`, `published_at`
- [x] `BlogPost` entity with author, tags, featured image
- [x] `NavigationMenu` entity for header/footer links
- [x] Content types: page, blog post, legal page (privacy, terms template)
- [x] Hybrid model: CMS pages use theme sections for layout ([ADR-012](../00-meta/adr/012-hybrid-cms-theme-sections-content-types.md))
- [x] Tenant isolation on all content entities

### 1.2 Page Builder UI

- [x] Drag-and-drop section editor reusing theme section types from Volume 6
- [x] New section types: `rich-text`, `image-banner`, `faq-accordion`, `testimonials`, `video-embed`
- [x] Live preview with mobile/desktop toggle
- [x] SEO panel: meta title, description, OG image, canonical URL
- [x] Schedule publish and unpublish
- [x] Version history (last 10 versions Phase 2)
- [x] Default pages seeded: About, Contact, Privacy Policy, Shipping Policy, Return Policy

### 1.3 Blog & SEO

- [x] Blog index at `/blog` with pagination
- [x] Blog post template with author, date, tags, related posts
- [x] RSS feed at `/blog/feed.xml`
- [x] Sitemap includes CMS pages and blog posts
- [x] Structured data: `Article`, `BreadcrumbList`

**Gate §1:** Merchant publishes landing page with hero + FAQ → indexed in sitemap → Lighthouse SEO ≥ 90.

---

## §2 Search & Merchandising Maturity (Weeks 22–26)

### 2.1 Search Enhancements

Per [Volume 10 Ch. 04](../10-infrastructure/04-postgresql-redis-meilisearch.md):

- [x] Typo tolerance (Meilisearch / pg_trgm) — synonym dictionary shipped; `pg_trgm` operator used when available
- [x] Synonym dictionary (platform Nigerian English variants + merchant CRUD)
- [x] Faceted filters: price range, availability, product type
- [x] Search analytics: top queries, zero-result queries in admin
- [x] Autocomplete p95 ≤ 100ms (NFR target)
- [x] Search result ranking: text relevance + sales velocity boost

### 2.2 Merchandising

Per [Volume 5 Ch. 03](../05-commerce-engine/03-collections-and-categories.md):

- [x] Smart collections: best sellers (30-day window), new arrivals, on sale
- [x] Collection scheduling (start/end dates)
- [x] Product tags for filtering and collection rules
- [x] Related products on PDP (same collection + manual override)
- [x] Homepage section: "Recently viewed" for returning visitors (cookie-based)

---

## §3 AI Platform v1 (Weeks 24–30)

Build per [Volume 9 Ch. 01–04](../09-ai-platform/README.md):

### 3.1 AI Infrastructure

**Checklist:**

- [x] AI provider abstraction: OpenAI GPT-4o primary, fallback model configured
- [x] Prompt template registry with version control
- [x] Token usage metering per tenant ([Volume 16 Ch. 05](../16-saas-multi-tenancy/05-usage-metering.md))
- [x] Rate limits: 50 AI requests/day Starter, 200 Growth, 500 Pro
- [x] PII scrubbing before prompt submission (no customer email in prompts)
- [x] AI audit log: prompt hash, model, tokens, tenant_id, feature
- [x] Opt-out setting for merchants who disable AI features

### 3.2 Phase 2 AI Features

| Feature | User | Acceptance |
|---------|------|------------|
| Product description generator | Merchant | Generate from title + 3 keywords; edit before save |
| SEO meta generator | Merchant | Title + description from product content |
| Collection description | Merchant | Generate from collection rules |
| Support reply suggest | Merchant staff | Suggest reply from order context |
| Zero-result search suggest | System | Log + suggest product additions to merchant |

**Checklist:**

- [x] Product description: merchant clicks "Generate" → editable draft in ≤ 5 seconds
- [x] Collection description generator (rules-aware draft; merchant edits before save)
- [x] Support reply suggest from order context (merchant edits before send)
- [x] Zero-result search → product addition suggestions in admin Search
- [x] AI features require explicit merchant action (no auto-publish)
- [x] Generated content watermark in audit log
- [x] Usage dashboard in admin: tokens consumed this month
- [x] Fallback to manual entry if AI provider unavailable (graceful degradation)

**Gate §3:** 30% of active merchants use AI description generator within 60 days of launch.

---

## §4 Advanced Commerce (Weeks 26–32)

Per [Volume 5 Ch. 13–14](../05-commerce-engine/13-subscriptions-and-gift-cards.md):

### 4.1 Digital Products

- [x] Product type: `digital` with file upload to R2
- [x] Secure download link generation post-payment (signed URL, 72h expiry)
- [x] Download limit per purchase (configurable)
- [x] No shipping required for digital-only orders

### 4.2 Gift Cards

- [x] Gift card product type with preset denominations (₦5,000, ₦10,000, ₦25,000)
- [x] Unique code generation; balance tracking
- [x] Redemption at checkout
- [x] Partial redemption supported

### 4.3 Customer Accounts (Enhanced)

- [x] Customer registration and login on storefront
- [x] Order history and address book
- [x] Wishlist (localStorage Phase 2; synced Phase 3)

---

## §5 Infrastructure Phase 2 Scale (Weeks 21–32)

Per [Volume 10 Ch. 10](../10-infrastructure/10-scaling-path-kubernetes.md):

### 5.1 Topology Upgrade

```text
Phase 2 (500–5,000 merchants):
  Load balancer → 2+ app VMs (FrankenPHP Octane)
  PostgreSQL primary + read replica
  PgBouncer connection pooling
  Redis cluster (primary + replica)
  Meilisearch dedicated VM
  Horizon workers: 3+ instances (webhooks, notifications, search, AI)
```

**Checklist:**

- [ ] Read replica for storefront API and admin list queries
- [ ] Write queries remain on primary
- [ ] PgBouncer pool size tuned: max 100 connections per app VM
- [ ] Horizontal Horizon workers with queue priority: `webhooks` > `notifications` > `default` > `search`
- [ ] Zero-downtime deploy: rolling restart with health check
- [ ] Staging environment mirrors Phase 2 topology
- [ ] Database migration backward-compatible strategy enforced

### 5.2 Outbox Pattern

Per [Volume 3 Ch. 07](../03-architecture/07-event-driven-communication.md):

- [x] Outbox table for guaranteed external event delivery
- [x] Outbox poller publishes to webhook endpoints
- [x] At-least-once delivery with consumer idempotency

### 5.3 Custom Domains

Per [Volume 16 Ch. 07](../16-saas-multi-tenancy/07-custom-domains.md):

- [x] DNS verification via TXT record
- [x] Cloudflare SSL certificate provisioning
- [x] Custom domain routing in storefront middleware
- [x] Growth plan entitlement check

**Gate §5:** k6 load test: 2,000 concurrent shoppers, p95 API < 500ms on Phase 2 staging.

---

## §6 Operations Maturity (Weeks 24–34)

Per [Volume 14](../14-operations/README.md):

**Checklist:**

- [ ] On-call rotation with PagerDuty; escalation policy documented
- [ ] Public status page at `status.sapphital.com` ([Volume 14 Ch. 08](../14-operations/08-status-page-and-communication.md))
- [ ] Runbooks: RB-001 deploy, RB-002 rollback, RB-003 database restore, RB-004 webhook backlog
- [ ] SLO error budget policy: freeze features when budget exhausted
- [ ] Weekly ops review: incidents, SLO, cost, queue depth
- [ ] Support ticket system integrated (Intercom or Freshdesk)
- [ ] Merchant support macros for top 20 issues
- [ ] Synthetic monitoring from Nigeria probe (checkout every 5 minutes)

---

## §7 Vertical Theme Expansion (Weeks 28–32)

Per [Volume 6](../06-theme-engine/README.md):

| Theme | Aesthetic | Target Merchant |
|-------|-----------|-----------------|
| Chop & Serve | Appetite-led, immediate ordering | Restaurants, bakeries, meal delivery |
| Studio Pro | Authority, outcomes, editorial cases | Services, agencies, consultants |
| Academy Path | Progression, instructor trust | Courses, schools, bootcamps |
| Launchpad | Product demo and proof | Software, downloads, subscriptions |

**Checklist:**

- [ ] Expansion themes pass Lighthouse ≥ 90 mobile (staging verification)
- [x] Theme picker in admin onboarding and theme settings
- [x] Themes implement canonical sections plus required vertical sections
- [x] Theme preview before apply
- [x] Theme switching portability report retains merchant-owned content

---

## §8 Merchant MFA & Security Phase 2

- [x] MFA mandatory for merchant Owner accounts
- [x] Session management: view active sessions, revoke
- [x] Login notification email on new device
- [x] API token rotation UI

---

## §9 Phase 2 Growth — Complete Checklist

| # | Workstream | Gate | Status |
|---|------------|------|--------|
| 1 | CMS page builder | Gate §1 | ☑ |
| 2 | Blog + SEO | Gate §1 | ☑ |
| 3 | Search facets + analytics | §2 | ☑ |
| 4 | Smart collections | §2 | ☑ |
| 5 | AI description + SEO gen | Gate §3 | ☑ |
| 6 | Digital products + gift cards | §4 | ☑ |
| 7 | Customer accounts | §4 | ☑ |
| 8 | Infra Phase 2 topology | Gate §5 | ☐ |
| 9 | Outbox pattern | §5.2 | ☑ |
| 10 | Custom domains | §5.3 | ☑ |
| 11 | On-call + status page | §6 | ☐ |
| 12 | Food, Services, Education, Digital themes | §7 | ☑ |
| 13 | Merchant MFA | §8 | ☑ |

---

## Phase 2 Exit Criteria

- [ ] CMS pages live for ≥ 50% of active merchants
- [ ] AI features adopted by ≥ 30% of merchants
- [ ] Monthly GMV tracking toward ₦500M
- [ ] 99.9% SLO sustained on Phase 2 infrastructure
- [ ] Zero-downtime deploy proven in production
- [ ] OWASP ASVS L2 ≥ 95% verified
- [ ] Support SLA: first response ≤ 4 business hours

---

## Dependencies

| Volume | Usage |
|--------|-------|
| [Volume 7](../07-cms/README.md) | CMS and page builder |
| [Volume 9](../09-ai-platform/README.md) | AI platform v1 |
| [Volume 10](../10-infrastructure/README.md) | Phase 2 scaling |
| [Volume 14](../14-operations/README.md) | Operations maturity |
| [Volume 15 Ch. 01](../15-future-roadmap/01-roadmap-overview.md) | H2 horizon alignment |
| [Volume 6](../06-theme-engine/README.md) | Additional themes |

## Next Chapter

Proceed to [Chapter 08 — Platform & Marketplace Playbook](./08-phase3-platform-marketplace-playbook.md) when Phase 2 exit criteria met.

---

## References

- [Volume 15 — Future Roadmap](../15-future-roadmap/README.md)
- [Volume 2 Ch. 10 — Technology Roadmap](../02-market-research/10-technology-roadmap-and-risks.md)
- [ADR-012 — Hybrid CMS Theme Sections](../00-meta/adr/012-hybrid-cms-theme-sections-content-types.md)
