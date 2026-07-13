# Platform Financial Services

**Package:** `platform/financial-services`  
**Version:** 0.1.0  
**Layer:** Platform Service (Layer 2)  
**Traceability:** ADR-019, ADR-023, Platform OS Ch. 13

## Purpose

Financial Services Layer (FSL) — payment intents, ledger, gateway orchestration, and money movement primitives consumed by Commerce and Marketplace.

## Phase 1 Scope (Nigeria GA)

- Paystack payment initialize / verify (storefront checkout)
- Webhook handling with signature validation and deduplication
- Missed webhook recovery: `scp:reconcile-pending-payments`
- Nightly audit: `scp:reconcile-nightly`
- Merchant refunds: `POST /api/v1/commerce/orders/{id}/refund` (full or partial, idempotent)

## References

- [Platform OS Ch. 13 §4](../../../docs/03-architecture/13-platform-os-architecture.md)
- [ADR-019 Financial Services Layer](../../../docs/00-meta/adr/019-financial-services-layer.md)
- [Vol 5 Ch. 16](../../../docs/05-commerce-engine/16-financial-services-layer.md)
