# Volume 16: SaaS Multi-Tenancy & Billing

**Document ID:** SCP-SAAS-001  
**Version:** 1.1.0  
**Status:** ✅ Active  
**Depends On:** Volume 3 (Architecture), ADR-002, ADR-011, Volume 11 (NDPA)  
**Owner:** Sapphital Learning Company  
**Lead Architect:** Stephen Musyoka Makola  

---

## Purpose

Volume 16 specifies SCP's **SaaS commercial layer** and **Tenant Provisioning Engine (TPE)** — tenant lifecycle, multi-store, entitlements, billing, domains, and automated business provisioning after Create Store.

## Scope

- SaaS business model and tenant lifecycle
- Plans, entitlements, and quotas
- Billing, invoicing, and tax display
- Usage metering (API, AI, storage, GMV)
- Feature flags per plan and tenant
- Custom domain provisioning
- SaaS acceptance criteria for Nigeria GA

- Platform Admin operator console (landlord equivalent)
- Platform marketing site, signup funnel, plan checkout
- Platform plan coupons and trial programs
- Localization admin and translation editor

## Out of Scope

- Merchant end-customer subscriptions (Volume 5 Ch. 13)
- Marketplace vendor fees (Volume 8 Ch. 04)
- Detailed payment gateway integration (Volume 5 Ch. 08)
- Domain **reseller** and cPanel provisioning (intentionally omitted — ADR-022)

## Chapters

| # | Chapter | Status |
|---|---------|--------|
| 01 | [SaaS Overview](./01-saas-overview.md) | ✅ Active |
| 02 | [Tenant Lifecycle](./02-tenant-lifecycle.md) | ✅ Active |
| 03 | [Plans & Entitlements](./03-plans-and-entitlements.md) | ✅ Active |
| 04 | [Billing & Invoicing](./04-billing-and-invoicing.md) | ✅ Active |
| 05 | [Usage Metering](./05-usage-metering.md) | ✅ Active |
| 06 | [Feature Flags](./06-feature-flags.md) | ✅ Active |
| 07 | [Custom Domains](./07-custom-domains.md) | ✅ Active |
| 08 | [SaaS Acceptance Criteria](./08-saas-acceptance-criteria.md) | ✅ Active |
| 09 | [AI-Guided Merchant Onboarding](./09-ai-guided-merchant-onboarding.md) | ✅ Active |
| 10 | [Tenant Provisioning Engine (TPE)](./10-tenant-provisioning-engine.md) | ✅ Active |
| 11 | [Platform Admin Operator Guide](./11-platform-admin-operator-guide.md) | ✅ Active |
| 12 | [Platform Marketing Site & Signup Funnel](./12-platform-marketing-site-and-signup.md) | ✅ Active |
| 13 | [Platform Plan Coupons & Trials](./13-platform-plan-coupons-and-trials.md) | ✅ Active |
| 14 | [Localization Admin & Translations](./14-localization-admin-and-translations.md) | ✅ Active |

## Traceability

| Requirement | Volume 16 Coverage |
|-------------|-------------------|
| NFR-040 | Tenant isolation in billing data |
| NFR-071 | Nigeria data residency for tenant records |
| NFR-083 | NDPA on billing PII |
| PRD-003 | SaaS plans and onboarding |
| ADR-002 | Shared DB + RLS for tenancy tables |

## Related Volumes

- [Volume 3 Ch. 05 — Multi-Tenancy](../03-architecture/05-multi-tenancy-and-isolation.md)
- [Volume 10 Ch. 11 — Cost Models](../10-infrastructure/11-cost-models.md)
- [Volume 5 Ch. 08 — Payments](../05-commerce-engine/08-payments-nigeria-africa.md)

---

**Review cycle:** Quarterly alignment with finance and product pricing
