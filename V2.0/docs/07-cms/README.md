# Volume 7: CMS & Page Builder

**Document ID:** SCP-CMS-001  
**Version:** 1.0.0  
**Status:** ✅ Active  
**Depends On:** Volume 3 (Architecture), Volume 4 (SDS), Volume 5 (Commerce), Volume 6 (Theme Engine), ADR-001, ADR-002, ADR-003, ADR-005, ADR-008, ADR-009, ADR-011  
**Owner:** Sapphital Learning Company  
**Lead Architect:** Stephen Musyoka Makola  

---

## Purpose

Volume 7 specifies the **Content Management System**, **visual page builder**, and **education commerce** surfaces of the SAPPHITAL Commerce Platform (SCP). Merchants use this volume's capabilities to publish storefront pages, blogs, navigation, SEO, media, and Sapphital course experiences without leaving the commerce operating system.

CMS is the **content layer**; the Theme Engine ([ADR-003](../00-meta/adr/003-theme-engine-react-json-schema.md)) is the **presentation layer**. Page layouts reuse the same JSON section/block schema as themes. Education products are catalog extensions (`Product.type = digital_course`), not a separate storefront.

## Scope

- Pages, landing pages, and system pages
- Visual page builder (Theme Engine–aligned)
- Structured content types
- Blog engine and navigation
- SEO metadata, sitemaps, redirects
- Media library (tenant-scoped CDN)
- Publishing, versioning, releases, localization
- Education commerce: courses, lessons, enrollments, drip, certificates, events
- Admin/Storefront APIs, domain events, security (SSRF, uploads, tenant isolation)
- Forms and lead capture (contact, newsletter, custom fields)

## Out of Scope

- Theme package development and Theme Store (Volume 6)
- Physical product catalog, cart, and checkout mechanics (Volume 5) — CMS consumes them
- Full AI agent orchestration (Volume 9) — CMS defines integration points only
- SCORM / Open Badges enterprise packaging (Phase 4 roadmap)
- Legal copy of Privacy Policy / Terms (legal counsel)

## Chapters

| # | Chapter | Status |
|---|---------|--------|
| 01 | [CMS Overview](./01-cms-overview.md) | ✅ Active |
| 02 | [Content Model](./02-content-model.md) | ✅ Active |
| 03 | [Page Builder Architecture](./03-page-builder-architecture.md) | ✅ Active |
| 04 | [Block Library](./04-block-library.md) | ✅ Active |
| 05 | [Editor UX & Workflows](./05-editor-ux-workflows.md) | ✅ Active |
| 06 | [SEO & Metadata](./06-seo-and-metadata.md) | ✅ Active |
| 07 | [Blog & Navigation](./07-blog-and-navigation.md) | ✅ Active |
| 08 | [Media Library](./08-media-library.md) | ✅ Active |
| 09 | [Education Commerce Pages](./09-education-commerce-pages.md) | ✅ Active |
| 10 | [API, Events & Security](./10-api-events-security.md) | ✅ Active |
| 11 | [Forms & Lead Capture](./11-forms-and-lead-capture.md) | ✅ Active |

## Design Decisions (Proposed ADRs)

## Design Decisions (ADRs)

| ADR | Title | Chapter |
|-----|-------|---------|
| [ADR-012](../00-meta/adr/012-hybrid-cms-theme-sections-content-types.md) | Hybrid CMS — Theme sections + structured content types | 01, 03 |
| [ADR-013](../00-meta/adr/013-blocknote-canonical-rich-text.md) | BlockNote as canonical rich-text format | 04, 07 |
| [ADR-014](../00-meta/adr/014-release-based-content-scheduling.md) | Release-based content scheduling (Timeline) | 05 |
| [ADR-015](../00-meta/adr/015-hybrid-localization-model.md) | Hybrid localization (field + document) | 02, 06 |
| [ADR-016](../00-meta/adr/016-learning-as-catalog-extension.md) | Learning as catalog extension | 09 |

## Phase Alignment

| Phase | CMS deliverable |
|-------|-----------------|
| Phase 3 | Pages, builder, blog, nav, SEO, media, versioning, scheduling |
| Phase 3.5 | Courses, enrollments, drip, certificates, education subscriptions |
| Phase 4 | SCORM, Open Badges, real-time collab editing |

## Related Volumes

- [Volume 6 — Theme Engine](../06-theme-engine/README.md)
- [Volume 5 — Commerce Engine](../05-commerce-engine/README.md)
- [Volume 11 — Security](../11-security/README.md)
- [Volume 15 — Future Roadmap](../15-future-roadmap/README.md) (Academy ecosystem)

## Acceptance Criteria (Volume Complete)

- [ ] All 11 chapters published with no placeholder sections
- [ ] Block library P0 blocks documented
- [ ] SEO sitemap and JSON-LD rules defined
- [ ] Education commerce tied to Volume 5 digital entitlements
- [ ] SSRF and tenant isolation in Chapter 10
- [ ] Nigeria mobile-first editor and media upload documented

---

**Sign-off roles:** Lead Architect, Product lead, Security reviewer.
