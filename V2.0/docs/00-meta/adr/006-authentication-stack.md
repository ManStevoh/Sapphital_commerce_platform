# ADR-006: Authentication Stack

**Date:** 2026-07-12  
**Status:** Accepted  
**Deciders:** Stephen Musyoka Makola  
**Volume Reference:** Volume 11 — Security

## Context

SCP serves Nigerian merchants (primary), Kenyan merchants, customers, platform admins, and third-party developers. Authentication must meet OWASP ASVS 5.0 V6–V9, Nigeria NDPA access controls, and low-end Android device realities common in African markets.

## Decision

| Surface | Phase 1 | Phase 2+ |
|---------|---------|----------|
| Merchant/customer web | Laravel Fortify + Sanctum (session cookies) | Same |
| Merchant MFA | TOTP recommended; mandatory for owners | Mandatory all merchant roles |
| Platform admin | Separate guard; MFA mandatory Phase 1 | WebAuthn hardware keys Phase 3 |
| API (merchant) | Sanctum personal access tokens, scoped | OAuth 2.1 + PKCE for app marketplace |
| Password hashing | Argon2id | Same |
| Customer passwordless | — | Phone OTP (Nigeria/Kenya) |

Token format: prefix-identifiable (`scp_live_`, `scp_test_`) for secret scanning (Stripe pattern).

## Alternatives Considered

| Alternative | Pros | Cons | Why Rejected |
|-------------|------|------|--------------|
| JWT-only auth | Stateless | Harder revocation; tenant context in token risk | Sanctum simpler for monolith |
| SMS-only MFA | Familiar in Africa | SIM-swap risk; ASVS discourages SMS-only | TOTP primary; SMS backup Phase 2 |
| Passport OAuth day one | Full OAuth server | Complexity before app ecosystem exists | Deferred to Phase 3 |

## Consequences

- Account enumeration resistance (uniform error messages)
- Login rate limit: 5/min per account + per IP (NFR-036)
- Breached-password check via HIBP k-anonymity API

## References

- OWASP ASVS 5.0 V6–V9: https://asvs.dev/v5.0.0/
- NFR-029, NFR-032, NFR-033
