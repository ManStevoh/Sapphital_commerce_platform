# ADR-014: Release-Based Content Scheduling (Timeline)

**Date:** 2026-07-12  
**Status:** Accepted  
**Deciders:** Stephen Musyoka Makola  
**Volume Reference:** Volume 7 — CMS

## Context

Merchants run campaigns (Black Friday, Ramadan sales, product launches) requiring coordinated publication of pages, navigation, theme settings, and structured content. Per-entity `publish_at` timestamps alone do not support atomic multi-page launches or preview of a future state. Shopify uses "markets" and theme publishing; Contentful uses releases; SCP needs a first-class scheduling model compatible with Theme Engine (ADR-003) and tenant isolation (ADR-002).

## Decision

**Implement content releases as first-class tenant resources:**

1. **Release entity** — Named container (`draft`, `scheduled`, `published`, `archived`) with optional `publish_at` datetime (Africa/Lagos default display; UTC storage).
2. **Membership** — Pages, content entries, navigation menus, and theme setting overrides attach to a release via `release_id`.
3. **Preview** — Signed preview tokens encode `tenant_id`, `release_id`, and optional `as_of` datetime for stakeholder review without affecting live storefront.
4. **Publish job** — At `publish_at`, worker atomically promotes release members to live pointers; previous live release becomes archived (retained for rollback).
5. **Rollback** — One-click revert to prior archived release within 30-day window (plan-gated on Volume 16).
6. **Live vs scheduled** — Exactly one `published` release per tenant storefront at a time; scheduled releases queue in priority order.

## Alternatives Considered

| Alternative | Pros | Cons | Why Rejected |
|-------------|------|------|--------------|
| Per-entity scheduled publish only | Simple | No atomic campaigns; broken navigation mid-publish | Unacceptable for campaign UX |
| Git-based content branches | Developer-friendly | Too complex for SME merchants | Poor Nigeria-first UX |
| Duplicate entire tenant snapshot | Easy rollback | Storage cost; slow diff | Overkill for MVP |
| External CMS scheduling | Mature | Violates hybrid CMS (ADR-012) | Architectural divergence |

## Consequences

### Positive

- Coordinated launches for pages + nav + theme settings
- Preview URLs for agency/client approval workflows
- Audit trail: who scheduled, who published (ADR-009)
- Aligns with commerce `PriceList` effective dates (Volume 5)

### Negative

- Editor UX complexity — merchants must understand releases
- Background job infrastructure required (Volume 10)
- Conflict resolution when two releases touch same entity

### Neutral

- Default release auto-created on tenant signup (`Initial Launch`)
- Webhook `content.release.published` for integrations (Volume 12)

## Compliance Notes

- Scheduled content with PII must not appear in public CDN cache until publish
- Preview tokens expire ≤ 72 hours; scoped to specific release

## References

- [Volume 7 Ch. 05 — Editor UX Workflows](../../07-cms/05-editor-ux-workflows.md)
- [Volume 7 Ch. 03 — Page Builder Architecture](../../07-cms/03-page-builder-architecture.md)
- ADR-012, ADR-003, ADR-009
