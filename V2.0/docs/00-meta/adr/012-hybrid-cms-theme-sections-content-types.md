# ADR-012: Hybrid CMS — Theme Sections + Structured Content Types

**Date:** 2026-07-12  
**Status:** Accepted  
**Deciders:** Stephen Musyoka Makola  
**Volume Reference:** Volume 7 — CMS; Volume 6 — Theme Engine

## Context

SCP merchants need both **visual storefront pages** (hero, product grids, testimonials) and **structured content** (blog posts, FAQs, policy pages, custom collections). The Theme Engine (ADR-003) already defines sections/blocks for layout. A separate headless CMS or a second page builder would duplicate layout logic, increase merchant confusion, and violate the modular monolith principle (ADR-001).

Nigeria-first merchants often launch with WhatsApp/Instagram traffic to simple landing pages; they need fast time-to-first-page without hiring developers. Enterprise merchants need typed content models for SEO, localization, and API delivery.

## Decision

**Adopt a hybrid CMS model:**

1. **Storefront layout** — Theme Engine JSON sections/blocks (ADR-003) for all merchant-facing page composition.
2. **Structured content** — Tenant-scoped `ContentType` schemas with field definitions, validation, and REST/GraphQL delivery.
3. **Binding** — CMS pages reference theme templates; structured entries feed theme sections via section settings or dynamic sources (e.g., blog list section reads `BlogPost` entries).
4. **Single admin UX** — One page builder that edits theme sections; structured types use form-based editors, not a parallel drag-and-drop canvas.
5. **No arbitrary HTML/JS** — Merchants cannot inject raw scripts; rich content uses BlockNote (ADR-013) within defined fields.

## Alternatives Considered

| Alternative | Pros | Cons | Why Rejected |
|-------------|------|------|--------------|
| Pure headless CMS (Contentful-style) | API-first, flexible | Heavy for SMEs; slow onboarding; duplicates theme layout | Poor fit for Nigeria SME time-to-value |
| Separate Webflow-style visual builder | Familiar UX | Diverges from ADR-003; two layout engines to maintain | Doubles cost and inconsistency |
| WordPress multisite | Mature CMS | Tenant isolation, performance, security at SaaS scale | Fails multi-tenant and RLS model (ADR-002) |
| Theme-only (no structured types) | Simple | Blog, FAQ, policies become unstructured; weak SEO/API | Insufficient for education and headless use cases |

## Consequences

### Positive

- One layout system (Theme Engine) for all storefront pages
- Structured types enable SEO metadata, localization (ADR-015), and Storefront API
- Education products (ADR-016) share catalog/checkout without a second CMS
- Clear bounded context: `Content` module owns types/entries; Theme owns presentation

### Negative

- Page builder UX must teach "sections vs content types" distinction
- Theme developers must expose section settings for dynamic content sources
- Migration from pure-headless competitors requires content-type mapping

### Neutral

- Default content types shipped per vertical (retail, education, services)
- Custom content types gated by plan entitlements (Volume 16)

## Compliance Notes

- Tenant isolation via RLS on all content tables (ADR-002, ADR-005)
- NDPA: content entries containing PII follow retention and export rules (Volume 11, Volume 20)
- WCAG 2.2 AA: structured fields enforce alt text and heading hierarchy in theme rendering

## References

- [Volume 7 Ch. 01 — CMS Overview](../../07-cms/01-cms-overview.md)
- [Volume 7 Ch. 03 — Page Builder Architecture](../../07-cms/03-page-builder-architecture.md)
- ADR-003, ADR-013, ADR-015, ADR-016
