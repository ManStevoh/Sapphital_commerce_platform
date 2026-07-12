# Chapter 05: Security Testing

**Document ID:** SCP-SEC-001-05  
**Version:** 1.0.0  
**Status:** 📝 Draft  
**Traceability:** NFR-040, NFR-042, NFR-043  

---

## 1. Testing Pyramid (Security Layer)

| Layer | Tests | Tooling | Gate |
|-------|-------|---------|------|
| Unit/feature | Authz matrix; validation negatives; state machines | Pest/PHPUnit | PR merge |
| **Tenant isolation suite** | Cross-tenant API, DB, cache, search, queue, files | Auto-generated Pest suite | **Blocking PR** |
| SAST | Larastan, Semgrep, ESLint security | CI | PR merge |
| Secrets scan | gitleaks | Pre-commit + CI | Blocking |
| Dependencies | composer/npm audit, Trivy, SBOM | CI + Renovate | Block critical/high |
| DAST | OWASP ZAP baseline | Weekly staging | Ticket SLA |
| Headers/TLS | Mozilla Observatory, testssl.sh | CI cron | A-grade target |
| Pentest | ASVS 5.0 L2 scoped (white-box) | External firm | Pre Phase 2 launch, annual |

## 2. Tenant Isolation Suite (Flagship)

For **every** tenant-scoped model, verify Tenant A cannot:

- Read, update, delete, or list Tenant B resources via API
- Access via direct UUID enumeration
- Retrieve via search index
- Hit cached data from another tenant
- Process queue job with wrong tenant context
- Access file URLs from another tenant's storage path
- Bypass via unscoped direct DB query (RLS returns zero rows)

**Nigeria relevance:** Multi-tenant SaaS handling millions of Nigerian consumer records — one isolation bug is an NDPC-reportable breach.

## 3. Nigeria/PCI Payment Tests

| Test | Expected |
|------|----------|
| Replayed Paystack webhook (stale timestamp) | Rejected |
| Replayed webhook (duplicate event ID) | Idempotent no-op |
| Client-side price tampering | Server recomputes; rejects mismatch |
| Checkout page third-party script | Blocked by CSP on locked template |
| Order marked paid without PSP verify | Impossible (state machine) |

## 4. Regulatory Readiness Tests

| Test | Validates |
|------|-----------|
| Data export job | NFR-077, NDPA access right |
| Account deletion flow | Erasure + 30-day recovery window |
| Consent log retrieval | Marketing consent audit |
| Breach simulation drill | 72h NDPC notification path |

## 5. CI Security Gates

```text
PR opened
  → gitleaks
  → composer audit / npm audit
  → Larastan + Semgrep
  → tenant isolation suite
  → unit authz tests
  → merge blocked on failure
```

## 6. Pentest Scope (Phase 2 Gate)

White-box pentest mapped to ASVS 5.0 L2 chapters, with emphasis on:

- Cross-tenant isolation
- Payment webhook abuse
- OAuth scope escalation (if Developer Platform live)
- AI prompt injection
- Admin impersonation controls

Black-box alone is **insufficient** for ASVS L2 verification per OWASP guidance.

---

## References

- OWASP ASVS 5.0 testing guidance: https://asvs.dev/v5.0.0/
- OWASP ZAP: https://www.zaproxy.org/
