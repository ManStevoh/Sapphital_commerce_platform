# ADR-007: Secrets Management

**Date:** 2026-07-12  
**Status:** Accepted  
**Deciders:** Stephen Musyoka Makola  
**Volume Reference:** Volume 10 — Infrastructure; Volume 11 — Security

## Context

SCP handles merchant credentials, PSP API keys (Paystack, Flutterwave), webhook secrets, and encryption keys. Nigeria NDPA and Kenya DPA require appropriate technical measures for confidentiality. NFR-045 prohibits secrets in source code.

## Decision

**Phase 1 (bootstrap):**

- Encrypted environment variables via deployment tooling (Laravel Forge, GitHub Actions secrets, or equivalent)
- `APP_KEY` + Laravel encrypted casts for column-level secrets
- gitleaks/trufflehog in CI (blocking)
- Unique webhook HMAC secret per PSP integration

**Phase 2 (scale):**

- HashiCorp Vault or cloud KMS (AWS/GCP) with envelope encryption
- Documented key rotation runbook (annual minimum; immediate on compromise)
- Dynamic DB credentials where supported

## Alternatives Considered

| Alternative | Pros | Cons | Why Rejected (Phase 1) |
|-------------|------|------|------------------------|
| Vault from day one | Best practice | Ops overhead for 1–5 person team | Phase 2 |
| Secrets in `.env` on server only | Simple | No audit trail; rotation painful | Insufficient for enterprise sales |

## Consequences

- NDPC CAR and ODPC audits can demonstrate secret handling procedures
- Rotation drill required before Phase 2 GA (Volume 11 acceptance criteria)

## References

- NFR-045, NFR-083, NFR-084
