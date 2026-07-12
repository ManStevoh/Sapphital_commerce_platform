# ADR-008: Edge Security Provider — Cloudflare

**Date:** 2026-07-12  
**Status:** Accepted  
**Deciders:** Stephen Musyoka Makola  
**Volume Reference:** Volume 10 — Infrastructure; Volume 11 — Security

## Context

SCP storefronts and APIs serve Nigeria (primary), Kenya, and broader Africa. Edge security must provide WAF, DDoS protection, bot management, and low latency without a dedicated security operations team.

## Decision

Use **Cloudflare** as the primary edge security and CDN provider:

- WAF managed rules + OWASP Core Ruleset (log-only tuning → blocking)
- Bot Management + **Turnstile** (CAPTCHA-free) on signup, login failures, checkout (NFR-046)
- Rate limiting at edge (per-IP) + in-app per-tenant limits (Redis)
- TLS 1.3 full-strict to origin
- R2 object storage (same vendor ecosystem)

Nigeria and Kenya both benefit from Cloudflare's African PoPs (including Lagos region expansion).

## Alternatives Considered

| Alternative | Pros | Cons | Why Rejected |
|-------------|------|------|--------------|
| AWS CloudFront + WAF | AWS-native | Higher complexity; less integrated bot UX | Team size |
| Self-hosted WAF | Control | Unmaintainable for small team | Rejected |

## Consequences

- Vendor coupling accepted for operational simplicity
- Custom domain SSL via Cloudflare for merchant stores

## References

- Cloudflare WAF: https://developers.cloudflare.com/waf/
- Cloudflare Turnstile: https://developers.cloudflare.com/turnstile/
- NFR-030, NFR-046
