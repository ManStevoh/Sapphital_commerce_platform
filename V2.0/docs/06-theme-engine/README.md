# Volume 6: Theme Engine

**Document ID:** SCP-THE-006  
**Version:** 1.2.0  
**Status:** ✅ Active  
**Depends On:** Volume 3, Volume 4, ADR-003  
**Owner:** Sapphital Learning Company  
**Lead Architect:** Stephen Musyoka Makola  

---

## Purpose

Volume 6 specifies SCP's **Theme Engine** — React + JSON schema storefront themes, section/block system, SDK, Theme Store, rendering pipeline, performance, security, and migration for Nigeria-primary mobile commerce.

## Scope

- Theme architecture (React + JSON schema per ADR-003)
- Template schema and section/block system
- Rendering pipeline (Next.js SSR/ISR)
- Theme SDK, CLI, and merchant theme editor
- Theme Store marketplace
- Asset delivery and performance budgets
- CSP, sandbox, and security
- Theme versioning and migrations
- Storefront Engine: eight experience layers, three-system architecture (ADR-017), performance engine

## Out of Scope

- SDS design tokens (Volume 4)
- CMS page builder content model (Volume 7)
- Platform CDN configuration detail (Volume 10)

## Chapters

| # | Chapter | Status |
|---|---------|--------|
| 01 | [Theme Engine Overview](./01-theme-engine-overview.md) | ✅ Active |
| 02 | [Template Schema Specification](./02-template-schema-specification.md) | ✅ Active |
| 03 | [Sections, Blocks & App Blocks](./03-sections-blocks-app-blocks.md) | ✅ Active |
| 04 | [Rendering Pipeline & RSC](./04-rendering-pipeline-rsc.md) | ✅ Active |
| 05 | [Theme Editor — Merchant UX](./05-theme-editor-merchant-ux.md) | ✅ Active |
| 06 | [Theme SDK & CLI](./06-theme-sdk-and-cli.md) | ✅ Active |
| 07 | [Theme Marketplace](./07-theme-marketplace.md) | ✅ Active |
| 08 | [Assets & Performance](./08-assets-and-performance.md) | ✅ Active |
| 09 | [Security, Sandbox & CSP](./09-security-sandbox-csp.md) | ✅ Active |
| 10 | [Migration & Versioning](./10-migration-and-versioning.md) | ✅ Active |
| 11 | [Reference Themes, Section Catalog & Portability](./11-reference-themes-section-catalog.md) | ✅ Active |
| 12 | [Storefront Engine — Eight Experience Layers](./12-storefront-engine-eight-layers.md) | ✅ Active |
| 13 | [Runtime, Visual Builder & Theme Engine Separation](./13-runtime-visual-builder-separation.md) | ✅ Active |
| 14 | [Storefront Performance Engine](./14-storefront-performance-engine.md) | ✅ Active |

## Related Volumes

- [Volume 4 — Design System](../04-design-system/README.md)
- [Volume 12 — Developer Platform](../12-developer-platform/README.md)
- [ADR-003 — Theme Engine](../00-meta/adr/003-theme-engine-react-json-schema.md)

## Acceptance Criteria (Volume Complete)

- [ ] All 14 chapters published with Document IDs
- [ ] ADR-017 three-system boundaries documented and enforced in CI
- [ ] Theme JS budget ≤ 100 KB enforced at publish
- [ ] CSP documented for production storefront
- [ ] Theme Store review pipeline cross-referenced
- [ ] Three launch themes (Lagos Atelier, Savanna Market, Terminal Tech) meet Lighthouse ≥ 90 mobile
- [ ] Reference themes pass five-second comprehension and UX distinctiveness gates
- [ ] Theme switching produces a portability report and never silently deletes merchant content

---

**Sign-off roles:** Lead Architect, Frontend Lead, Security reviewer (Volume 11 cross-check).
