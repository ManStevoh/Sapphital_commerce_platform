# ADR-004: Checkout Integration Model — PSP Redirect vs Embedded Iframe

**Date:** 2026-07-12  
**Status:** Accepted  
**Deciders:** Stephen Musyoka Makola  
**Volume Reference:** Volume 5 — Commerce Engine; Volume 11 — Security

## Context

SCP must accept payments across Nigeria (Paystack, Flutterwave, OPay), Kenya (M-Pesa), and card networks without storing cardholder data (NFR-044). PCI DSS v4.0.1 SAQ A r1 (effective March 2025) introduced a new eligibility criterion for merchants using embedded payment iframes: script-attack protection on pages hosting the iframe (formerly requirements 6.4.3 and 11.6.1, now an eligibility gate).

Because SCP controls the storefront theme layer, any embedded checkout increases PCI scope for every merchant storefront.

## Decision

**Phase 1:** Use **PSP-hosted redirect or fully hosted checkout pages** as the default integration model:

- **Nigeria:** Paystack redirect, Flutterwave hosted checkout, bank USSD flows
- **Kenya:** M-Pesa STK Push (no card data), Paystack Kenya redirect
- **Cards globally:** Stripe Checkout hosted page where available

Embedded iframes (Stripe Elements, Paystack inline) are **deferred to Phase 2** and only offered on a locked-down checkout template with strict CSP, no third-party scripts, and PSP written confirmation of script-attack protection per PCI SSC FAQ 1588.

## Alternatives Considered

| Alternative | Pros | Cons | Why Rejected (Phase 1) |
|-------------|------|------|------------------------|
| Embedded iframe default | Smoother UX, less redirect friction | SAQ A eligibility criterion; every merchant theme must enforce checkout CSP | PCI burden on platform + merchants |
| Direct card API (SAQ D) | Full UX control | SCP stores/processes card data | Violates NFR-044; unacceptable scope |
| Redirect only | Lowest PCI burden; FAQ 1588 exempts script criterion | User leaves storefront briefly | Acceptable trade-off for launch |

## Consequences

### Positive

- Platform and merchants remain SAQ A eligible
- No PAN/CVV in SCP infrastructure
- Simpler security audits for Nigeria NDPC CAR and enterprise sales

### Negative

- Slightly lower conversion vs seamless embedded UX (mitigate with return URL optimization, mobile deep links)
- More PSP redirect flows to test per country

## Engineering Principles Impact

| Principle | Impact |
|-----------|--------|
| Secure by Default | Minimizes payment data exposure |
| User Experience First | Phase 2 embedded option with guarded template |
| Extensible | Payment provider abstraction supports both models |

## References

- PCI SSC SAQ A updates (March 2025): https://blog.pcisecuritystandards.org/important-updates-announced-for-merchants-validating-to-self-assessment-questionnaire-a
- PCI FAQ on iframe eligibility: https://blog.pcisecuritystandards.org/faq-clarifies-new-saq-a-eligibility-criteria-for-e-commerce-merchants
- NFR-044, NFR-083
