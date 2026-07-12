# Chapter 03: Unit & Integration Tests (Pest, Vitest)

**Document ID:** SCP-TEST-001-03  
**Version:** 1.0.0  
**Status:** ✅ Active  
**Traceability:** NFR-037, NFR-038, NFR-040  

---

## 1. Purpose

Establish standards for backend unit/feature tests (**Pest** on Laravel 12) and frontend unit/component tests (**Vitest** on Next.js), including integration tests that exercise HTTP, database, queue, and cache boundaries.

## 2. Scope

- Laravel API, domain services, policies, jobs, events, webhooks
- Next.js admin, vendor portal, shared design system components
- PostgreSQL 16 test database with RLS policies enabled (ADR-002, ADR-005)

## 3. Out of Scope

- Browser E2E (Chapter 05)
- Load testing (Chapter 06)
- Cross-tenant isolation matrix (Chapter 04 — separate gate)

---

## 4. Backend — Pest Configuration

### 4.1 Project Layout

```text
tests/
├── Pest.php                    # Global expectations, helpers
├── TestCase.php                # Base Laravel test case
├── Unit/
│   ├── Commerce/
│   ├── Identity/
│   └── ...
├── Feature/
│   ├── Commerce/
│   ├── Identity/
│   └── ...
├── Integration/
│   ├── Webhooks/PaystackTest.php
│   └── Search/MeilisearchSyncTest.php
└── Support/
    ├── ActsAsTenant.php
    ├── CreatesMerchants.php
    └── WebhookPlayer.php
```

### 4.2 Base Test Case Requirements

Every Feature/Integration test MUST:

1. Call `RefreshDatabase` or `DatabaseTransactions` explicitly
2. Set tenant context via `actingAsTenant(Tenant $tenant)` before tenant-scoped actions
3. Reset tenant context in `tearDown` to prevent bleed across tests
4. Use factories — never hard-coded UUIDs shared across files

### 4.3 Tenant Context Helper

```php
// tests/Support/ActsAsTenant.php
trait ActsAsTenant
{
    public function actingAsTenant(Tenant $tenant, ?User $user = null): static
    {
        TenantContext::set($tenant);
        $this->withHeader('X-Tenant-Id', $tenant->uuid);

        if ($user) {
            $this->actingAs($user);
        }

        // ADR-005: SET LOCAL for RLS in integration tests
        DB::statement("SET LOCAL app.tenant_id = ?", [$tenant->id]);

        return $this;
    }
}
```

### 4.4 Pest Style Guidelines

```php
describe('OrderCheckout', function () {
    beforeEach(function () {
        $this->tenant = TenantFactory::new()->ngn()->create();
        $this->actingAsTenant($this->tenant, UserFactory::new()->merchant()->create());
    });

    it('recomputes totals server-side and rejects client tampering', function () {
        $product = ProductFactory::new()->priced(500000)->create(); // ₦5,000.00 in kobo

        $response = $this->postJson('/api/v1/checkout/sessions', [
            'lines' => [['variant_id' => $product->defaultVariant->uuid, 'qty' => 1]],
            'client_total' => 100, // tampered
        ]);

        $response->assertUnprocessable()
            ->assertJsonPath('errors.client_total.0', 'Total mismatch.');
    });

    it('transitions order to paid only after webhook verification', function () {
        // ...
    })->group('commerce', 'pci');
});
```

### 4.5 Policy & Authorization Tests

Every route with authorization MUST have Pest tests covering:

| Case | Expected |
|------|----------|
| Unauthenticated | 401 |
| Wrong role | 403 |
| Wrong tenant | 404 (not 403 — no resource existence leak) |
| Correct role + tenant | 200/201 |

Use `Gate::inspect()` in unit tests; HTTP tests for integration.

### 4.6 Webhook Integration Tests

Paystack/Flutterwave sandbox fixtures in `tests/Fixtures/Webhooks/`:

| Scenario | Assertion |
|----------|-----------|
| Valid signature + fresh timestamp | Order → `paid` |
| Invalid HMAC | 401, order unchanged |
| Duplicate event ID | 200 idempotent, single audit entry |
| Stale timestamp (&gt; 5 min) | 401 |
| Amount mismatch | 422, alert logged |

### 4.7 Queue & Event Tests

```php
it('dispatches OrderPlaced with tenant id in payload', function () {
    Queue::fake();

    // action...

    Queue::assertPushed(OrderPlaced::class, fn ($job) =>
        $job->tenantId === $this->tenant->id
    );
});
```

---

## 5. Frontend — Vitest Configuration

### 5.1 Project Layout

```text
apps/
├── admin/
│   └── src/
│       ├── components/__tests__/
│       └── hooks/__tests__/
├── storefront/
└── packages/
    └── design-system/
        └── src/__tests__/
vitest.config.ts          # Root or per-app
vitest.setup.ts           # Testing Library, matchMedia mock
```

### 5.2 Vitest Standards

| Rule | Detail |
|------|--------|
| Runner | Vitest 2.x with `@vitejs/plugin-react` |
| DOM | `@testing-library/react` + `@testing-library/user-event` |
| Network | MSW 2.x for API mocks; no live API in unit tests |
| Snapshots | Limited to design-system icons; prefer explicit assertions |
| Coverage | `@vitest/coverage-v8`; exclude `*.stories.tsx` |

### 5.3 Component Test Example

```typescript
import { render, screen } from '@testing-library/react';
import { PriceDisplay } from '@scp/design-system';
import { describe, it, expect } from 'vitest';

describe('PriceDisplay', () => {
  it('formats NGN amounts for Nigeria storefront', () => {
    render(<PriceDisplay amountMinor={500000} currency="NGN" locale="en-NG" />);
    expect(screen.getByText(/₦5,000\.00/)).toBeInTheDocument();
  });

  it('exposes accessible price text for screen readers', () => {
    render(<PriceDisplay amountMinor={500000} currency="NGN" locale="en-NG" />);
    expect(screen.getByLabelText(/price/i)).toHaveAccessibleName();
  });
});
```

### 5.4 Hook & Store Tests

Test cart state, checkout step machine, and form validation with Vitest + MSW. Business rules that duplicate backend logic should **match API contract tests** — frontend tests validate UX, not authoritative pricing.

---

## 6. Integration Test Patterns

### 6.1 API Contract Shape

Assert RFC 7807 Problem Details on errors:

```php
$response->assertStatus(422)
    ->assertJsonStructure(['type', 'title', 'status', 'detail', 'errors']);
```

### 6.2 Database Assertions

```php
expect(Order::where('tenant_id', $this->tenant->id)->count())->toBe(1);
expect(DB::table('audit_logs')->where('tenant_id', $this->tenant->id)->exists())->toBeTrue();
```

### 6.3 Cache Integration

Use dedicated Redis DB index in CI. Verify cache keys include tenant prefix:

```php
$key = CacheKeys::product($product->id);
expect($key)->toStartWith("t:{$this->tenant->uuid}:");
```

### 6.4 Search Integration

Meilisearch test container with tenant-filtered index. Assert documents from Tenant A do not appear in Tenant B search API.

---

## 7. Test Data & Factories

| Factory | Traits |
|---------|--------|
| `TenantFactory` | `->ngn()`, `->kes()`, `->withPlan('growth')` |
| `ProductFactory` | `->digital()`, `->withVariants(3)` |
| `OrderFactory` | `->pending()`, `->paid()`, `->withPaystackRef()` |
| `UserFactory` | `->merchant()`, `->vendor()`, `->platformAdmin()` |

Nigeria fixtures use **kobo** (integer minor units) to avoid float rounding bugs.

---

## 8. Running Tests (Local)

```bash
# Backend — full suite
./vendor/bin/pest

# Backend — parallel (CI mirrors this)
./vendor/bin/pest --parallel --processes=4

# Backend — filter
./vendor/bin/pest --group=commerce

# Frontend — design system
pnpm --filter @scp/design-system test

# Frontend — coverage
pnpm --filter @scp/admin test:coverage
```

**Note:** Documentation only; no npm install/build required to author specs.

---

## 9. CI Integration

| Job | Command | Gate |
|-----|---------|------|
| `pest-unit` | `pest --parallel tests/Unit` | Merge |
| `pest-feature` | `pest --parallel tests/Feature tests/Integration` | Merge |
| `vitest` | `pnpm -r test --run` | Merge |

See Chapter 09 for full pipeline.

---

## 10. Security Considerations

- Never assert against production URLs in tests
- Webhook fixtures use rotated test secrets only
- PII in factory output uses Faker; no real Nigerian phone numbers from production dumps

---

## 11. Acceptance Criteria

- [ ] All FormRequest rules have at least one negative Pest test
- [ ] All public API endpoints have happy-path + auth failure integration tests
- [ ] Design system components have Vitest coverage ≥ 80%
- [ ] Webhook tests cover replay, tamper, and idempotency (PCI-related)
- [ ] `ActsAsTenant` used in 100% of tenant-scoped Feature tests

---

## 12. Related ADRs

- ADR-002 — Tenant scoping in factories and helpers
- ADR-004 — Payment webhook test requirements
- ADR-006 — Auth stack test patterns (Sanctum, MFA)

## 13. Sources

- Pest PHP documentation: https://pestphp.com/
- Vitest documentation: https://vitest.dev/
- Laravel 12 testing: https://laravel.com/docs/testing
