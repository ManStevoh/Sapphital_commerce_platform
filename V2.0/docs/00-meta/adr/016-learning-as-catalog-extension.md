# ADR-016: Learning as Catalog Extension

**Date:** 2026-07-12  
**Status:** Accepted  
**Deciders:** Stephen Musyoka Makola  
**Volume Reference:** Volume 7 — CMS; Volume 5 — Commerce Engine; Volume 15 — Future Roadmap

## Context

Sapphital Learning Company operates an education business alongside SCP. Merchants (and Sapphital Academy) must sell **courses, bundles, and digital credentials** using the same checkout, payments (Paystack, Nigeria-first), tax, and order history as physical goods. Separate LMS checkout (Teachable, Thinkific) would fragment revenue reporting, customer accounts, and NDPA data subject requests.

## Decision

**Model learning products as first-class catalog extensions, not a separate commerce stack:**

1. **Product type** — `LearningProduct` extends catalog `Product` with `product_type = learning`; variants represent pricing tiers (one-time, installment where supported).
2. **Curriculum hierarchy** — `Course` → `Module` → `Lesson` stored in `Learning` bounded context; linked to product via `product_id`.
3. **Entitlement** — On paid order fulfillment, grant `Enrollment` records tied to `customer_id` and `order_line_id`; RLS scoped by tenant.
4. **Content delivery** — Lesson bodies use BlockNote (ADR-013); video via signed URLs from tenant media or embedded provider (Volume 10 CDN).
5. **Storefront** — Theme sections (`course-hero`, `curriculum-list`, `lesson-player`) in Theme Engine (ADR-003); CMS pages market courses via structured types (ADR-012).
6. **Progress** — `LessonProgress`, `QuizAttempt` in Learning module; optional SCORM deferred to Volume 15 horizon.
7. **Academy integration** — Sapphital Academy as tenant `sapphital-academy` with federation API for catalog sync (Volume 15 Ch. 12).
8. **Refunds** — Revoke enrollment on full refund; partial refund policy tenant-configurable.

## Alternatives Considered

| Alternative | Pros | Cons | Why Rejected |
|-------------|------|------|--------------|
| External LMS + iframe checkout | Fast to market | Split payments, accounts, compliance | Poor unified merchant UX |
| Separate `orders_learning` table | Isolated | Duplicate checkout, tax, reporting | Violates modular monolith |
| Digital downloads only (no progress) | Simple | No course UX | Insufficient for Academy |
| Marketplace-only courses | No platform ownership | Sapphital Academy needs first-party | Business requirement |

## Consequences

### Positive

- One cart, one Paystack flow, one customer record for mixed baskets (book + course)
- Unified analytics and NDPA export across commerce + learning
- Education merchants use familiar product admin patterns
- Clear upgrade path to certificates and cohorts (Volume 15)

### Negative

- Catalog admin UX more complex (product types)
- Video streaming costs and DRM expectations managed per plan
- Lesson player accessibility (WCAG) is platform responsibility

### Neutral

- Free preview lessons via `is_preview` flag without enrollment
- B2B seat licensing deferred to enterprise tier (Volume 15, Volume 16)

## Compliance Notes

- Enrollment records are personal data under NDPA; export/delete with customer account
- Minor learners: tenant policy flag; parental consent flow documented Volume 20
- Nigeria GAID: marketing to students follows consent rules

## References

- [Volume 7 Ch. 09 — Education Commerce Pages](../../07-cms/09-education-commerce-pages.md)
- [Volume 15 Ch. 12 — Sapphital Academy Integration](../../15-future-roadmap/12-sapphital-academy-integration.md)
- [Volume 5 — Commerce Engine](../../05-commerce-engine/README.md)
- ADR-012, ADR-013, ADR-004
