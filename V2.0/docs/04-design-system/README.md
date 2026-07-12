# Volume 4: SAPPHITAL Design System (SDS)

**Document ID:** SCP-DS-001  
**Version:** 1.3.0  
**Status:** ✅ Active  
**Depends On:** Volume 1 (Vision & Product Principles), Volume 3 (Architecture), [ADR-003](../00-meta/adr/003-theme-engine-react-json-schema.md)  
**Owner:** Sapphital Learning Company  

---

## Purpose

Volume 4 defines the **SAPPHITAL Design System (SDS)** — the single source of truth for visual language, interaction patterns, component architecture, and UX standards across every SCP surface: platform admin, merchant dashboard, vendor portal, storefront, checkout, and theme SDK.

SDS exists so that merchants, customers, and operators experience one coherent product at **Shopify / Stripe / Linear** quality, optimized for **Nigeria mobile-first** commerce (low-end Android, 3G networks, NGN formatting, local payment methods).

## Scope

- Design philosophy and product-principle alignment
- Design tokens (color, typography, spacing, elevation, motion)
- Component architecture (`primitive → base → business → page`)
- Core component inventory and specifications
- Admin, merchant, storefront, and checkout UX patterns
- WCAG 2.2 Level AA accessibility (NFR-047 – NFR-053)
- Theme inheritance and merchant customization (ADR-003)
- Figma and Storybook governance
- Performance and UX budgets (NFR-001, NFR-006, NFR-009, NFR-010)
- Living digital salesperson UX: segment homepages, customer portal, fintech checkout, social surfaces

## Out of Scope

- Theme SDK implementation details (Volume 6)
- Backend API contracts (Volumes 5, 12)
- Infrastructure CDN configuration (Volume 10)

## Technology Stack

| Layer | Choice | Rationale |
|-------|--------|-----------|
| UI framework | React 19 | Server Components, concurrent features, theme SDK alignment |
| App framework | Next.js (App Router) | SSR/ISR for storefront performance (NFR-001) |
| Language | TypeScript | Type-safe tokens, components, theme settings |
| Styling | Tailwind CSS v4 | Utility-first; maps directly to design tokens |
| Primitives | shadcn/ui (Radix) | Accessible, composable, own-the-code model |
| Icons | Lucide React | Consistent stroke, tree-shakeable |
| Motion | Framer Motion | Respects `prefers-reduced-motion` (NFR-052) |

## Chapters

| # | Chapter | Status |
|---|---------|--------|
| 01 | [Design Philosophy & Principles](./01-design-philosophy-and-principles.md) | ✅ Active |
| 02 | [Design Tokens](./02-design-tokens.md) | ✅ Active |
| 03 | [Typography & Iconography](./03-typography-and-iconography.md) | ✅ Active |
| 04 | [Color, Elevation & Motion](./04-color-elevation-motion.md) | ✅ Active |
| 05 | [Component Architecture](./05-component-architecture.md) | ✅ Active |
| 06 | [Core Component Inventory](./06-core-component-inventory.md) | ✅ Active |
| 07 | [Admin & Merchant Dashboard UX](./07-admin-and-merchant-dashboard-ux.md) | ✅ Active |
| 08 | [Storefront & Checkout UX](./08-storefront-and-checkout-ux.md) | ✅ Active |
| 09 | [Accessibility (WCAG 2.2 AA)](./09-accessibility-wcag-22.md) | ✅ Active |
| 10 | [Theme Inheritance & Customization](./10-theme-inheritance-and-customization.md) | ✅ Active |
| 11 | [Design Governance (Figma & Storybook)](./11-design-governance-figma-storybook.md) | ✅ Active |
| 12 | [Performance & UX Budgets](./12-performance-and-ux-budgets.md) | ✅ Active |
| 13 | [Storefront Visual Direction & Conversion Patterns](./13-storefront-visual-direction.md) | ✅ Active |
| 14 | [Intelligent Homepage, Customer Portal & Fintech Checkout](./14-intelligent-homepage-customer-portal.md) | ✅ Active |
| 15 | [AI-Guided Onboarding UX](./15-ai-guided-onboarding-ux.md) | ✅ Active |

## Traceability

| Requirement | SDS Implementation |
|-------------|-------------------|
| NFR-047 – NFR-053 | Chapter 09; token contrast in Chapters 02, 04 |
| NFR-001, NFR-006 | Chapter 12 |
| NFR-009, NFR-010 | Chapter 12 |
| NFR-050, NFR-051 | Chapters 02, 04, 09 |
| NFR-052 | Chapters 04, 12 |
| Product Principles 1–10 | Chapter 01 |
| ADR-003 Theme Engine | Chapter 10 |
| PRD-001, PRD-006, FR-AI-003 | Chapters 08, 13 |

## Related Volumes

- **Volume 1** — Product principles and NFRs
- **Volume 6** — Theme engine, section/block editor
- **Volume 11** — Security; WCAG baseline reference
- **Volume 13** — Testing and QA (visual regression, a11y CI)

## Acceptance Criteria (Volume Complete)

Volume 4 is complete for Phase 1 Nigeria launch when:

- [ ] All 15 chapters approved by design + engineering leads
- [ ] Figma library `SCP-SDS-v1` published and linked from Chapter 11
- [ ] Storybook deployed with ≥ 90% of Chapter 06 inventory documented
- [ ] Token package `@scp/design-tokens` consumed by admin and storefront apps
- [ ] axe-core CI passes on checkout and admin order-detail flows
- [ ] Lighthouse mobile score ≥ 85 on reference storefront theme (Nigeria 3G profile)
- [ ] Merchant theme customization (colors, logo, fonts) validated on 3 built-in themes
- [ ] Reference themes pass Chapter 13 five-second comprehension and distinctiveness gates
- [ ] Golden homepage, collection, product, search, cart, and AI-assistant screens reviewed at mobile and desktop breakpoints

---

**Sign-off roles:** Lead Architect, Design Lead (TBD), Frontend Lead (TBD).
