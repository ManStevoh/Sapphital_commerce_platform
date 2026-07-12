# ADR-010: Admin Impersonation ("Login as Merchant")

**Date:** 2026-07-12  
**Status:** Accepted  
**Deciders:** Stephen Musyoka Makola  
**Volume Reference:** Volume 11 — Security

## Context

Platform support must debug merchant issues in Nigeria and across Africa. Impersonation is high-risk under NDPA/Kenya DPA (processor access to controller data) and must be controlled.

## Decision

**Allow impersonation** with strict controls:

1. Platform admin only (separate auth guard)
2. MFA step-up immediately before impersonation session
3. Visible banner in admin UI: "Viewing as [Merchant Name]"
4. Time-boxed session (max 1 hour)
5. Merchant notification email/SMS after session ends (configurable)
6. Full audit log entry: impersonator_id, target tenant, start/end, actions taken
7. Prohibited during checkout payment capture and payout detail changes (read-only or blocked)

## Alternatives Considered

| Alternative | Pros | Cons | Why Rejected |
|-------------|------|------|--------------|
| No impersonation | Maximum privacy | Slow support; higher churn in Nigeria market | Support burden too high |
| Unrestricted impersonation | Fast support | Regulatory and trust risk | Unacceptable |

## References

- Nigeria NDPA processor obligations (§39–40)
- NFR-041
