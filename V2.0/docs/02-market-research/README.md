# Volume 2: Market Research & Technology Strategy

**Document ID:** SCP-VOL-002  
**Version:** 1.0.0  
**Status:** ✅ Active  
**Last Updated:** 2026-07-12  
**Owner:** Sapphital Learning Company  
**Lead Architect:** Stephen Musyoka Makola  
**Depends On:** Volume 1 ✅, Volume 0 (ADRs) ✅

---

## Purpose

Volume 2 is the **evidence base** for SCP technology and market decisions. It answers the research program's ten questions (see `docs/00-meta/research-and-synthesis-program.md`) with cited sources, competitive analysis, and explicit tradeoffs that feed ADRs, NFRs, and the product roadmap.

Nigeria is the **primary market**; Kenya and East Africa are the **secondary launch corridor**; broader Africa expansion follows Phase 1 operational proof.

This volume does not replace Volume 3 (architecture) or Volume 5 (commerce modules). It informs them.

---

## Chapters

| # | Chapter | Description | Status |
|---|---------|-------------|--------|
| 01 | [Commerce Landscape 2026](01-commerce-landscape-2026.md) | Africa/Nigeria market sizing, trends, merchant segments | ✅ |
| 02 | [Competitive Analysis — Global Platforms](02-competitive-analysis-global-platforms.md) | Shopify, Stripe, commercetools, Medusa, WooCommerce, etc. | ✅ |
| 03 | [Competitive Analysis — Africa & Nigeria](03-competitive-analysis-africa-nigeria.md) | Paystack ecosystem, local platforms, market gaps | ✅ |
| 04 | [Payment & Fintech Strategy](04-payment-fintech-strategy.md) | Paystack, Flutterwave, M-Pesa, OPay, PCI SAQ A | ✅ |
| 05 | [Technology Evaluation Framework](05-technology-evaluation-framework.md) | How SCP scores and selects technologies | ✅ |
| 06 | [Backend & Frontend Stack Decisions](06-backend-frontend-stack-decisions.md) | Laravel 12, Next.js, PostgreSQL — rationale and alternatives | ✅ |
| 07 | [Data, Search, Caching & Storage](07-data-search-caching-storage.md) | PostgreSQL, Redis, Meilisearch, object storage strategy | ✅ |
| 08 | [AI Commerce Market Opportunity](08-ai-commerce-market-opportunity.md) | AI-native commerce trends, agentic workflows, SCP positioning | ✅ |
| 09 | [Strategic Positioning & Differentiation](09-strategic-positioning-and-differentiation.md) | Moats, pricing, GTM, win/loss scenarios | ✅ |
| 10 | [Technology Roadmap & Risks](10-technology-roadmap-and-risks.md) | Phased delivery, extraction path, risk register | ✅ |

---

## Evidence Classification

All claims in this volume use the research program confidence levels:

| Level | Name | Use in Volume 2 |
|-------|------|-----------------|
| E1 | Primary source | Vendor docs, standards, regulatory sites — justifies ADRs |
| E2 | Strong secondary | Analyst reports, benchmarks with methodology |
| E3 | Industry observation | Repeated product patterns, practitioner consensus |
| E4 | Hypothesis | Marked explicitly; requires validation |

---

## Traceability

| Artifact | Volume 2 Contribution |
|----------|----------------------|
| PRD-001 – PRD-020 | Market evidence supporting product requirements |
| NFR-001 – NFR-085 | Technology choices mapped to performance, security, residency |
| ADR-001 – ADR-011 | Research backing for accepted architecture decisions |

---

## Engineering Principles Compliance

| Principle | How This Volume Complies |
|-----------|--------------------------|
| UX First | Competitive UX benchmarks (Shopify, Linear) inform admin/storefront targets |
| Performance | Stack and caching research tied to NFR-001 – NFR-012 |
| API-First | Global platform API comparisons (Stripe, Shopify, Medusa) |
| Modular | Evaluation framework requires module boundary impact assessment |
| Decoupled | Stack decisions preserve Clean Architecture layers |
| AI Native | Dedicated AI commerce opportunity chapter with agent permissions research |
| Secure by Default | Payment PCI strategy, NDPA/Kenya DPA alignment in fintech chapter |
| Multi-Tenant | Scale projections from 10 to 100,000 merchants inform tenancy research |
| Extensible | Theme/plugin ecosystem analysis vs Shopify Liquid and WooCommerce |
| Observable | Roadmap includes observability tooling selection criteria |

---

## Related Volumes

- **Volume 1** — Product constitution, NFRs, competitive positioning summary
- **Volume 3** — System architecture implementing decisions here
- **Volume 5** — Commerce engine (payments, checkout, orders)
- **Volume 9** — AI platform specification
- **Volume 11** — Security and compliance (PCI, NDPA, Kenya DPA)

---

## Review Cadence

| Activity | Frequency | Owner |
|----------|-----------|-------|
| Market sizing refresh | Quarterly | Product |
| Competitive feature matrix | Monthly | Product + Engineering |
| Payment provider capability audit | Quarterly | Engineering |
| Technology evaluation scorecard | Per major ADR | Lead Architect |
| Risk register review | Monthly | Lead Architect |
