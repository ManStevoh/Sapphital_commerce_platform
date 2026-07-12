# Volume 8: Marketplace

**Document ID:** SCP-MKT-001  
**Version:** 1.0.0  
**Status:** ✅ Active  
**Depends On:** Volume 1 (Vision), Volume 5 (Commerce Engine), Volume 11 (Security), ADR-004, ADR-005, ADR-011  
**Owner:** Sapphital Learning Company  

---

## Purpose

This volume defines SCP's **multi-vendor marketplace** capability: how marketplace operators onboard sellers, manage catalog quality, calculate commissions, split payments through Nigerian PSPs, settle Naira payouts, resolve disputes, and maintain vendor trust — all within strict tenant isolation and NDPA data walls.

## Scope

- Marketplace operator and vendor personas (Nigeria-first)
- Vendor onboarding, KYC, and approval workflows
- Vendor portal and operator dashboard surfaces
- Commission, fee, and tax rules
- Paystack Subaccounts and Flutterwave Split Payments integration
- Payout scheduling, holds, and reconciliation in NGN
- Disputes, trust scores, and fraud controls
- Multi-vendor catalog ownership and moderation
- Vendor analytics and operator reporting
- Domain state machines, APIs, events, and background jobs
- NDPA compliance for vendor PII and cross-vendor isolation
- Phase 1 acceptance criteria

## Out of Scope

- Single-merchant store mode (Volume 5)
- Theme marketplace for developers (Volume 6)
- Plugin marketplace (Volume 12)
- Detailed shipping carrier integrations (Volume 5)
- Legal contract text for vendor agreements (legal counsel)

## Nigeria Context

SCP marketplace design explicitly addresses lessons from **Jumia** and local operator pain:

| Jumia Pattern | SCP Response |
|---------------|--------------|
| 10–20%+ commission with opaque fee stacking | Transparent fee schedule; operator-configurable tiers |
| Platform owns customer relationship | Merchant/marketplace operator owns customer data within tenant |
| Slow or opaque vendor payouts | Automated NGN settlement with auditable ledger |
| Inconsistent product quality | Listing moderation, vendor trust scores, strike system |
| Limited vendor self-service | Full vendor portal with analytics and payout visibility |

## Chapters

| # | Chapter | Document ID | Status |
|---|---------|-------------|--------|
| 01 | [Marketplace Overview](./01-marketplace-overview.md) | SCP-MKT-001-01 | ✅ Active |
| 02 | [Vendor Onboarding & KYC](./02-vendor-onboarding-kyc.md) | SCP-MKT-001-02 | ✅ Active |
| 03 | [Vendor Dashboard](./03-vendor-dashboard.md) | SCP-MKT-001-03 | ✅ Active |
| 04 | [Commissions & Fees](./04-commissions-and-fees.md) | SCP-MKT-001-04 | ✅ Active |
| 05 | [Split Payments & Naira Payouts](./05-split-payments-payouts-nigeria.md) | SCP-MKT-001-05 | ✅ Active |
| 06 | [Disputes, Trust & Safety](./06-disputes-trust-safety.md) | SCP-MKT-001-06 | ✅ Active |
| 07 | [Multi-Vendor Catalog](./07-multi-vendor-catalog.md) | SCP-MKT-001-07 | ✅ Active |
| 08 | [Vendor Analytics](./08-vendor-analytics.md) | SCP-MKT-001-08 | ✅ Active |
| 09 | [State Machines](./09-state-machines.md) | SCP-MKT-001-09 | ✅ Active |
| 10 | [API & Events](./10-api-events.md) | SCP-MKT-001-10 | ✅ Active |
| 11 | [Compliance & Tax](./11-compliance-tax.md) | SCP-MKT-001-11 | ✅ Active |
| 12 | [Acceptance Criteria](./12-acceptance-criteria.md) | SCP-MKT-001-12 | ✅ Active |

## Related Requirements

| ID | Requirement | Chapter |
|----|-------------|---------|
| PRD-008 | Multi-vendor marketplace mode | 01 |
| FR-006 | Vendor self-service portal | 03 |
| FR-020 | Tenant isolation on all entities | All |
| FR-021 | Money value object (integer kobo) | 04, 05 |
| NFR-040 | Zero cross-tenant data access | All |
| NFR-044 | PCI SAQ A (PSP redirect) | 05 |
| NFR-071 | Nigeria data residency | 11 |
| NFR-083 | NDPA compliance | 11 |
| NFR-085 | Pan-Africa privacy framework | 11 |

## Related ADRs

- [ADR-004](../00-meta/adr/004-checkout-psp-redirect-saq-a.md) — PSP redirect checkout
- [ADR-005](../00-meta/adr/005-rls-pgbouncer-set-local.md) — RLS tenant isolation
- [ADR-011](../00-meta/adr/011-data-residency-africa.md) — Nigeria primary residency

## Module Boundary

The **Marketplace** domain module lives inside the modular monolith at `App\Domains\Marketplace\`. It publishes interfaces to Commerce (Orders, Catalog, Payments) and consumes events from those modules. Vendors are sub-entities within a tenant; they never become separate tenants.
