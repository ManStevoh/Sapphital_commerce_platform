# Chapter 07: Security Testing

**Document ID:** SCP-TEST-001-07  
**Version:** 1.0.0  
**Status:** ✅ Active  
**Traceability:** NFR-029, NFR-040 – NFR-046, NFR-044, NFR-083  

---

## 1. Purpose

Define SCP's **security testing program** — SAST, DAST, dependency scanning, secrets detection, authz verification, and the **PCI DSS SAQ A compliance test pack** — complementing Volume 11 Security with executable standards.

Volume 11 Chapter 05 describes security testing strategy; **this chapter owns tooling, test cases, CI integration, and evidence retention.**

## 2. Scope

- Application security (OWASP ASVS 5.0 Level 2)
- Tenant isolation (cross-reference Chapter 04 — execution here)
- Payment security and PCI SAQ A (ADR-004)
- Nigeria NDPA technical controls (export, deletion, audit)
- Supply chain security

## 3. Security Testing Pyramid

| Layer | Tooling | Cadence | Gate |
|-------|---------|---------|------|
| Unit/feature authz | Pest | PR | Merge |
| **Tenant isolation suite** | Pest (generated) | PR | **Block** |
| SAST | Larastan, Semgrep, ESLint security plugins | PR | Merge |
| Secrets scan | gitleaks | Pre-commit + PR | **Block** |
| Dependency audit | composer audit, pnpm audit, Trivy | PR | Block critical/high |
| SBOM diff | Syft/Grype | Release | Review |
| DAST | OWASP ZAP baseline | Weekly staging | Ticket SLA |
| Headers/TLS | Mozilla Observatory, testssl.sh | Weekly | A-grade target |
| PCI compliance pack | Pest + Playwright `@pci` | Weekly + pre-release | **Block GA** |
| Pentest | External ASVS 5.0 L2 | Pre Phase 2, annual | Release |

---

## 4. SAST Standards

### 4.1 PHP (Laravel)

| Tool | Config | Fail On |
|------|--------|---------|
| Larastan | Level 6 minimum | New errors |
| Semgrep | `rules/scp/` custom + OWASP | High severity |
| Enlightn (optional) | Security checklist | Phase 2 |

Custom Semgrep rules:

- `scp-raw-sql-without-binding`
- `scp-cache-key-missing-tenant`
- `scp-mass-assignment-un guarded`

### 4.2 TypeScript (Next.js)

| Tool | Config |
|------|--------|
| ESLint | `@typescript-eslint`, `eslint-plugin-security` |
| Semgrep | React XSS, dangerous HTML |

---

## 5. DAST — OWASP ZAP

Weekly baseline scan against staging:

```text
Target: https://staging.scp.test
Auth: ZAP context with merchant + admin sessions
Policy: API-aware baseline (OpenAPI import)
```

| Finding Severity | SLA |
|------------------|-----|
| Critical | 24 hours |
| High | 7 days |
| Medium | 30 days |
| Low | Backlog |

False positives documented in `docs/11-security/appendices/zap-exceptions.md`.

---

## 6. Authz Matrix Tests

Every route registered in `routes/api.php` and Next.js API routes maps to Pest tests:

```php
// tests/Security/AuthzMatrixTest.php
dataset('protected routes', fn () => AuthzManifest::routes());

it('denies unauthenticated access to protected route', function ($method, $uri) {
    $this->json($method, $uri)->assertUnauthorized();
})->with('protected routes');
```

Manifest generated from route list + policy map. **100% route coverage** Phase 1 goal.

---

## 7. PCI DSS SAQ A Compliance Test Pack

SCP maintains **SAQ A eligibility** by never storing, processing, or transmitting cardholder data (NFR-044, ADR-004). The compliance pack provides **automated evidence** for quarterly reviews and ASV scan preparation.

### 7.1 Design Assertions (Static)

| Test ID | Assertion | Method |
|---------|-----------|--------|
| PCI-001 | No `card_number`, `cvv`, `pan` columns in migrations | Schema scan |
| PCI-002 | No card data in log patterns | Log fixture grep |
| PCI-003 | Checkout routes use redirect/hosted PSP only | Route + template audit |
| PCI-004 | CSP on checkout template blocks third-party scripts | Header test |
| PCI-005 | No card inputs in SCP DOM | Playwright `@pci` |
| PCI-006 | Webhook HMAC validation on all PSP endpoints | Pest integration |
| PCI-007 | Order cannot reach `paid` without verified webhook | State machine test |
| PCI-008 | No PAN regex in API responses | Response body scan |
| PCI-009 | TLS 1.2+ only on checkout domain | testssl.sh cron |
| PCI-010 | ASV scan scope excludes PSP domains | Config documentation |

### 7.2 Payment Flow Tests (Dynamic)

```php
describe('PCI SAQ A payment flow', function () {
    it('PCI-007 rejects marking order paid without webhook', function () {
        $order = OrderFactory::new()->pending()->create();

        expect(fn () => $order->markPaidWithoutVerification())
            ->toThrow(PaymentVerificationRequiredException::class);
    });

    it('PCI-006 rejects paystack webhook with invalid signature', function () {
        $payload = WebhookFixtures::paystack('charge.success');

        $this->postJson('/webhooks/paystack', $payload['body'], [
            'X-Paystack-Signature' => 'invalid',
        ])->assertUnauthorized();
    });
});
```

### 7.3 Nigeria PSP Coverage

| PSP | Sandbox Tests Required |
|-----|------------------------|
| Paystack | Redirect, webhook, refund webhook |
| Flutterwave | Hosted checkout, webhook |
| Bank USSD (Phase 2) | Status polling only — no card |

### 7.4 Evidence Bundle (Per Release)

```text
compliance/pci/{release-tag}/
├── saq-a-checklist.pdf          # signed manually
├── pci-test-pack-junit.xml
├── playwright-pci-report.html
├── asv-scan-report.pdf
└── psp-aoc-on-file.json         # metadata only
```

Retained 7 years per PCI record-keeping guidance.

---

## 8. Nigeria NDPA Technical Tests

| Test | Validates | Tool |
|------|-----------|------|
| Data export completeness | NFR-077, NDPA access right | Pest feature |
| Account deletion + recovery window | 30-day soft delete | Pest + E2E |
| Consent log retrieval | Marketing audit | Pest |
| Breach notification drill | 72h workflow | Tabletop (manual) |
| Cross-border transfer register | Subprocessor list API | Pest |

---

## 9. Secrets & Supply Chain

### 9.1 gitleaks

Pre-commit hook + CI:

```bash
gitleaks detect --source . --verbose --redact
```

Block on any finding in diff or history scan (nightly full history on `main`).

### 9.2 Dependency Policy

| Severity | Action |
|----------|--------|
| Critical | Block merge; patch within 48h |
| High | Block merge; patch within 7d |
| Medium | Ticket; patch within 30d |

Trivy scans container images on build.

---

## 10. CI Security Pipeline

```text
PR opened
  → gitleaks (diff)
  → composer audit + pnpm audit
  → Larastan + Semgrep + ESLint security
  → tenant isolation suite
  → authz matrix sample (full nightly)
  → PCI static tests (PCI-001 – PCI-004)
  → merge blocked on failure

Weekly staging
  → OWASP ZAP baseline
  → PCI dynamic pack (PCI-005 – PCI-010)
  → testssl.sh on checkout domain
```

---

## 11. Pentest Coordination (Phase 2 Gate)

White-box pentest mapped to ASVS 5.0 L2 with emphasis on:

- Cross-tenant isolation (verify automated suite completeness)
- Payment webhook abuse
- OAuth scope escalation (Developer Platform)
- AI prompt injection (Volume 9)
- Admin impersonation (ADR-010)

Black-box alone is **insufficient** for ASVS L2 per OWASP guidance.

---

## 12. Relationship to Volume 11

| Volume 11 | Volume 13 |
|-----------|-----------|
| Compliance framework, threat model | Executable tests proving controls |
| Security acceptance criteria | Release criteria mapping (Chapter 10) |
| Incident response | Breach drill tests |

---

## 13. Acceptance Criteria

- [ ] PCI test pack PCI-001 – PCI-010 automated and green pre-GA
- [ ] Zero critical/high dependency vulnerabilities in production images
- [ ] Tenant isolation suite 0 failures (Chapter 04)
- [ ] gitleaks clean on all PRs
- [ ] ZAP critical/high findings = 0 open on staging before release

---

## 14. Sources

- OWASP ASVS 5.0: https://asvs.dev/v5.0.0/
- PCI SSC SAQ A r1 (March 2025 updates)
- OWASP ZAP: https://www.zaproxy.org/
- Volume 11 Chapter 05 — Security Testing
