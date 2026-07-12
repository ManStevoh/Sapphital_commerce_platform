# ADR-019: Financial Services Layer (FSL)

**Date:** 2026-07-12  
**Status:** Accepted  
**Deciders:** Stephen Musyoka Makola  
**Volume Reference:** Volume 5 — Commerce Engine; Volume 8 — Marketplace

## Context

SCP's product strategy is **Commerce Infrastructure for Africa**, not an "African Shopify." African merchants ask whether they can receive **M-Pesa, bank transfer, mobile money, EFT, and local wallets** — not whether Stripe is available.

Embedding payment logic inside checkout, orders, subscriptions, and marketplace modules creates hardcoded gateway dependencies, blocks multi-country expansion, and prevents smart routing and split settlements.

## Decision

**Create a dedicated Financial Services Layer (FSL)** as a bounded context within the modular monolith. All money movement flows through FSL via stable interfaces.

### FSL Owns

- Payment gateway integrations (adapter pattern)
- Refund, capture, authorize, verify, cancel
- Webhook ingestion and idempotency
- Smart gateway routing (customer country, method, success rate)
- Split payments and settlement workflows
- Merchant wallets and ledger entries
- Marketplace escrow and payouts (with Volume 8)
- Subscription billing rails
- Financial reconciliation and audit trails
- Offline payment confirmation workflows

### FSL Does Not Own

- Cart and checkout UX (Commerce — calls FSL)
- Order fulfillment state (Orders)
- Tax rule definitions (Tax Engine — FSL executes collection splits)
- Shipping carrier logic (Logistics adapters — parallel pattern)

### Architecture

```text
Checkout / Orders / Marketplace / Subscriptions
                    ↓
         Financial Services Layer (FSL)
                    ↓
         Payment Engine + Ledger
                    ↓
         Gateway Interface (contract)
                    ↓
         Gateway Adapters (Paystack, M-Pesa, PayFast, …)
                    ↓
         Provider APIs
```

### Gateway Interface Contract

Every adapter implements:

| Method | Purpose |
|--------|---------|
| `pay()` | Initialize customer payment |
| `authorize()` | Pre-auth where supported |
| `capture()` | Capture authorized funds |
| `refund()` | Full or partial refund |
| `verify()` | Verify transaction status |
| `webhook()` | Parse and validate provider webhook |
| `status()` | Poll or query status |
| `cancel()` | Void pending payment |

Adapters declare: `supported_countries[]`, `supported_methods[]`, `supports_split`, `supports_payout`, `settlement_currencies[]`.

### Smart Routing

Payment Engine selects gateway by:

1. Customer country + currency
2. Payment method selected (mobile money first)
3. Merchant enabled gateways
4. Gateway health / success-rate score
5. Marketplace split requirements

Example: Kenya customer → M-Pesa adapter; Nigeria → Paystack; South Africa → PayFast; international card → Stripe.

Merchant configures enabled providers; platform may recommend defaults via Africa AI (Volume 9).

### Mobile Money First

Storefront and checkout UI order:

1. Mobile money (M-Pesa, MTN MoMo, Airtel Money, …)
2. Bank transfer / EFT
3. Wallets
4. Cards

### PCI Posture

Default remains **PSP redirect / hosted** (ADR-004, SAQ A). Adapters never receive raw PAN in SCP application tier.

## Alternatives Considered

| Alternative | Pros | Cons | Why Rejected |
|-------------|------|------|--------------|
| Payments inside Checkout module | Faster Phase 1 | Hardcoded; unmaintainable at Africa scale | Blocks strategic vision |
| Stripe-first global stack | Simple for cards | Wrong primary question for Africa | Market mismatch |
| One gateway per merchant only | Simple routing | Suboptimal conversion; no failover | Smart routing wins |
| Separate microservice Day 1 | Isolation | Ops overhead Phase 1 | Modular monolith module first (ADR-001) |

## Consequences

### Positive

- Add Uganda/Tanzania/Ghana/RSA gateways without touching checkout
- Unified reconciliation and audit across countries
- Split payments and marketplace settlements in one ledger
- Clear answer: **"We support Africa"** via provider catalog

### Negative

- Initial abstraction cost before second country launch
- Adapter certification workload per gateway
- Ledger correctness requires rigorous testing

### Neutral

- Phase 1 Nigeria still ships Paystack/Flutterwave first through same interface
- Stripe adapter for international customers Phase 2+

## References

- [Volume 5 Ch. 16 — Financial Services Layer](../../05-commerce-engine/16-financial-services-layer.md)
- [Volume 5 Ch. 17 — Gateway Adapters Africa](../../05-commerce-engine/17-payment-gateway-adapters-africa.md)
- ADR-004, ADR-001, ADR-011, ADR-023
