# ADR-011: Data Residency and Subprocessors — Africa-First

**Date:** 2026-07-12  
**Status:** Accepted  
**Deciders:** Stephen Musyoka Makola  
**Volume Reference:** Volume 11 — Security; Volume 10 — Infrastructure

## Context

SCP's **primary market is Nigeria**, with expansion across Africa. Nigeria NDPA §41–43 restricts cross-border transfers without adequate protection. Kenya DPA §48–50 has similar requirements. NFR-071 requires Phase 1 residency strategy.

## Decision

**Phase 1 data placement:**

| Data class | Primary region | Notes |
|------------|----------------|-------|
| Production PostgreSQL, Redis, app compute | **Nigeria (Lagos)** or nearest West Africa cloud region | Default for all tenants at launch |
| Kenya-optimized deployment | **Kenya/East Africa** region | Activated for KE merchants or latency routing |
| Object storage (media) | Same region as tenant primary | Tenant-scoped paths |
| Backups | Same region; encrypted | Cross-region DR copy documented in RoPA |

**Subprocessor policy:**

- Publish subprocessor list (Paystack, Flutterwave, Cloudflare, Sentry, AI providers, etc.)
- Standard Contractual Clauses / NDPA-compliant transfer mechanisms for US/EU subprocessors
- Record legal basis for each cross-border transfer (NDPA §41(1)(a) or Kenya DPA §48)
- Merchant Terms include DPA annex: SCP as **processor** for merchant customer data; Sapphital as **controller** for platform account data

**Phase 3:** EU/US dedicated regions for GDPR enterprise tier (NFR-072).

## Alternatives Considered

| Alternative | Pros | Cons | Why Rejected |
|-------------|------|------|--------------|
| Kenya-only residency | M-Pesa optimized | Wrong for Nigeria-primary strategy | Superseded |
| Single US region | Cheapest cloud | NDPA/Kenya DPA transfer complexity | Non-compliant default |

## Consequences

- NDPC registration as DCPMI likely required (financial/commerce platform processing at scale)
- Infrastructure cost slightly higher than single US region
- Clear competitive advantage vs Western platforms with no Africa residency story

## References

- Nigeria NDPA §41–43: https://ndpc.gov.ng/
- Kenya DPA 2019: https://www.odpc.go.ke/
- NFR-071, NFR-083, NFR-084, NFR-085
