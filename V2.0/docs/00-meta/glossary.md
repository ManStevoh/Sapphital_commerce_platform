# Glossary

Standard terminology for the SAPPHITAL Commerce Platform specification. All volumes must use these terms consistently.

---

## Platform Terms

| Term | Definition |
|------|------------|
| **SCP** | SAPPHITAL Commerce Platform — the complete SaaS commerce operating system |
| **SAPPHITAL Platform** | Parent platform providing shared services (auth, billing, AI, notifications) across SCP and future products |
| **Tenant** | An isolated merchant organization using SCP; all data is scoped to a tenant |
| **Store** | A single commerce storefront belonging to a tenant; a tenant may operate multiple stores |
| **Channel** | A sales surface (online store, POS, mobile app, marketplace, social) connected to a store |
| **Platform Admin** | Sapphital operator managing the SaaS platform itself |
| **Merchant** | Business owner or operator managing their store(s) on SCP |
| **Vendor** | Third-party seller on a multi-vendor marketplace store |
| **Customer** | End consumer purchasing from a merchant's store |

## Architecture Terms

| Term | Definition |
|------|------------|
| **Modular Monolith** | Single deployable application with strict internal domain boundaries |
| **Platform OS** | SAPPHITAL's eight-layer package model: Kernel, Platform Services, Business Products, Extensions, Connectors, AI Skills, Themes, Developer Marketplace (ADR-023) |
| **Platform Kernel** | Mandatory platform layer — auth, tenancy, billing, module manager; must not reference Commerce/ERP domain code |
| **Platform Service** | Reusable engine (payments, notifications, workflow) consumed by all business products |
| **Business Product** | Installable application package under `Modules/` (Commerce, ERP, CRM, POS) |
| **Connector** | Versioned external adapter under `Connectors/` (Paystack, M-Pesa, QuickBooks) |
| **Extension** | Optional feature package (Loyalty, Gift Cards) installable per tenant |
| **Module Manager** | Platform application for install, upgrade, dependency resolution, and health of packages |
| **module.json** | Machine-readable manifest for every installable package (name, semver, requires, permissions, routes) |
| **Domain Module** | Self-contained business domain (e.g., Orders, Products) with its own models, services, and events — may be internal to a Business Product package |
| **ADR** | Architecture Decision Record — documents a significant technical decision and its rationale |
| **Domain Event** | An immutable record that something happened in a domain (e.g., `OrderPlaced`) |
| **Action** | Single-purpose application service class executing one business operation |
| **Value Object** | Immutable object defined by its attributes, not identity (e.g., `Money`, `Address`) |
| **DTO** | Data Transfer Object — structured data passed between layers without behavior |
| **RLS** | Row-Level Security — PostgreSQL feature enforcing tenant data isolation at the database level |

## Commerce Terms

| Term | Definition |
|------|------------|
| **SKU** | Stock Keeping Unit — unique identifier for a product variant |
| **Variant** | A specific purchasable configuration of a product (size, color, etc.) |
| **Collection** | A curated group of products (manual or rule-based) |
| **Cart** | Temporary holding area for items a customer intends to purchase |
| **Checkout** | The multi-step flow converting a cart into an order |
| **Fulfillment** | Process of picking, packing, and shipping an order |
| **Settlement** | Transfer of funds from platform to vendor after commission deduction |
| **Commission** | Platform fee charged on marketplace vendor sales |

## Theme & Extension Terms

| Term | Definition |
|------|------------|
| **Theme** | A complete visual and structural package defining a store's appearance |
| **Section** | A configurable page region (hero, product grid, footer) editable by merchants |
| **Block** | An individual content element within a section (text, image, button) |
| **Theme SDK** | Developer toolkit for building, testing, and publishing themes |
| **Plugin** | An extension adding functionality via defined hook points |
| **Webhook** | HTTP callback notifying external systems of platform events |

## SaaS & Billing Terms

| Term | Definition |
|------|------------|
| **Plan** | A subscription tier defining features, limits, and pricing |
| **Subscription** | Recurring billing relationship between tenant and platform |
| **Usage Meter** | Tracked consumption metric (orders, storage, API calls) for billing |
| **Entitlement** | A feature or resource a tenant's plan grants access to |

## AI Terms

| Term | Definition |
|------|------------|
| **RAG** | Retrieval-Augmented Generation — AI responses grounded in tenant-specific data |
| **Agent** | An autonomous AI entity that can plan and execute multi-step tasks using tools |
| **Tool** | A callable function an AI agent can invoke (search products, create order) |
| **Embedding** | Vector representation of text used for semantic search |
| **Prompt Template** | Versioned, parameterized instruction set for AI models |

## Performance Terms

| Term | Definition |
|------|------------|
| **LCP** | Largest Contentful Paint — Core Web Vital measuring loading performance |
| **INP** | Interaction to Next Paint — Core Web Vital measuring interactivity |
| **CLS** | Cumulative Layout Shift — Core Web Vital measuring visual stability |
| **p95** | 95th percentile — 95% of requests complete within this time |
| **ISR** | Incremental Static Regeneration — Next.js strategy for cached static pages |

## Security Terms

| Term | Definition |
|------|------------|
| **RBAC** | Role-Based Access Control — permissions assigned via roles |
| **ASVS** | Application Security Verification Standard (OWASP); SCP targets **5.0** Level 2 |
| **CSP** | Content Security Policy — HTTP header preventing XSS |
| **WAF** | Web Application Firewall — filters malicious HTTP traffic |
| **SAQ A** | Self-Assessment Questionnaire A — PCI DSS compliance for hosted checkout |
| **NDPA** | Nigeria Data Protection Act 2023 — primary data protection law for SCP's main market |
| **NDPC** | Nigeria Data Protection Commission — regulator under NDPA |
| **GAID** | General Application and Implementation Directive 2025 — operational rules under NDPA |
| **DCPMI** | Data Controller/Processor of Major Importance — NDPC registration tier for large processors |
| **DPO** | Data Protection Officer — required role for DCPMIs; NDPC-certified in Nigeria |
| **CAR** | Compliance Audit Return — annual filing for certain DCPMI tiers under GAID |
| **RoPA** | Record of Processing Activities — mandatory inventory of data processing |
| **DPIA** | Data Privacy Impact Assessment — required for high-risk processing (e.g., AI profiling) |
| **ODPC** | Office of the Data Protection Commissioner — Kenya's data protection regulator |
