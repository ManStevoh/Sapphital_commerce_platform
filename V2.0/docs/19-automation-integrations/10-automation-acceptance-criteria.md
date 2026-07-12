# Chapter 10: Automation Acceptance Criteria

**Document ID:** SCP-AUT-001-10  
**Version:** 1.0.0  
**Status:** ✅ Active  

---

Volume 19 is **complete for Phase 1 Nigeria launch** when all criteria below pass. Phase 2 gates are noted separately.

## 1. Workflow Engine

- [ ] Merchant can create, publish, pause, and archive workflows from admin UI
- [ ] Template gallery includes Nigeria Essentials pack (order WhatsApp, payment SMS, low stock)
- [ ] Trigger evaluation ≤ 2 s p95 from domain event to first step enqueue
- [ ] Idempotency: 100 duplicate `order.paid` events → exactly 1 workflow run
- [ ] Delay steps resume correctly after worker restart (simulated kill test)
- [ ] Failed run visible with step-level error; manual replay succeeds
- [ ] Plan entitlements enforce workflow count limits (Starter 20, Business 100)

## 2. Event-Action Catalog

- [ ] All Phase 1 triggers emit documented JSON Schema payloads
- [ ] Paystack `charge.success` → normalized `payment.confirmed` + `order.paid` after reconciliation
- [ ] Paystack webhook HMAC-SHA512 verification rejects tampered payloads
- [ ] `checkout.abandoned` fires once per cart after 1-hour idle (configurable)
- [ ] Catalog API filters actions by installed connectors and plan

## 3. WhatsApp Channel

- [ ] Meta WhatsApp Cloud API embedded signup completes for test merchant
- [ ] Utility templates delivered: `order_confirmation_ng`, `order_shipped_ng`, `payment_received_ng`
- [ ] Template submission workflow shows Meta approval status
- [ ] Delivery webhooks update message log and CRM timeline
- [ ] order.paid → WhatsApp delivered ≤ 30 s p95 in staging (Lagos sim)
- [ ] Marketing template blocked without `whatsapp_marketing` consent

## 4. SMS Channel

- [ ] Termii integration sends transactional SMS in staging
- [ ] Africa's Talking configured as failover route
- [ ] E.164 normalization for +234 numbers verified
- [ ] STOP keyword opts out SMS marketing within 60 s
- [ ] Promotional SMS passes Termii DND check where API available
- [ ] Daily SMS caps enforced per plan

## 5. CRM Lite

- [ ] Customer timeline shows orders, messages, tags, notes, consent changes
- [ ] Tags add/remove manually and via `crm.add_tag` workflow action
- [ ] Consent captured at checkout with audit fields (source, timestamp, policy_version)
- [ ] CSV import 1,000 customers with duplicate detection report
- [ ] Customer merge consolidates orders and timeline with audit entry
- [ ] Tenant isolation: zero cross-tenant customer search in automated suite

## 6. Marketing Automation (Phase 2 Gate)

- [ ] Segment builder with predicates on order total, location, consent
- [ ] Abandoned cart journey: SMS at 2 h, WhatsApp at 24 h (consent-gated)
- [ ] Quiet hours 21:00–08:00 WAT enforced for promotional sends
- [ ] Frequency cap: 4th marketing touch in 7 days blocked
- [ ] Campaign analytics show sent, delivered, attributed order count
- [ ] Broadcast > 10,000 requires secondary admin confirmation

## 7. ERP Connectors (Phase 2 Gate)

- [ ] Zoho Books OAuth connect + mapping wizard + test invoice pass
- [ ] QuickBooks Online sales receipt on `order.paid` ≤ 2 min p95
- [ ] Idempotency: 50 retries on same order → one ERP invoice
- [ ] `refund.created` → credit note in connected ERP
- [ ] Paystack `settlement.processed` → daily fee journal mapping
- [ ] CSV Export Pro available on all plans (Phase 1)
- [ ] Reconciliation dashboard flags > 0.5% order count variance
- [ ] OAuth expiry triggers admin banner and `connector.auth.expired` event

## 8. Integration Marketplace (Phase 2–3 Gate)

- [ ] Marketplace browse, install, uninstall first-party Zoho app
- [ ] OAuth scopes displayed in plain language before consent
- [ ] Uninstall revokes tokens within 5 minutes (verified)
- [ ] Workflow template pack install creates editable copy
- [ ] Partner app scope escalation attempt returns 403
- [ ] Paid app billing via Paystack subscription documented

## 9. Automation Security

- [ ] RBAC: staff cannot execute sensitive commerce actions without permission
- [ ] Connector secrets encrypted; absent from logs and workflow JSON
- [ ] SSRF test suite blocks private IP and metadata URL targets
- [ ] Meta, Termii, Paystack webhook endpoints reject invalid signatures
- [ ] Marketing send pipeline fails closed without consent (automated test)
- [ ] Audit log contains workflow publish, connector connect, campaign send events
- [ ] Anomaly rule pauses tenant on SMS burst > 3× baseline (staging simulation)

## 10. Performance & Operations

- [ ] Horizon `automation` queue lag p95 ≤ 5 s under 500 concurrent runs/tenant load test
- [ ] Integration queue alert fires when oldest job > 1 hour (injected test)
- [ ] Message delivery success rate ≥ 97% utility WhatsApp over 7-day staging soak
- [ ] Automation metrics exported: runs, step duration, delivery rate
- [ ] Runbook: Meta template rejection, Termii balance exhaustion, ERP OAuth expiry

## 11. Compliance (Nigeria NDPA)

- [ ] WhatsApp and SMS providers listed in RoPA with transfer mechanism
- [ ] Privacy policy discloses automation and messaging subprocessors
- [ ] Customer data export includes consent records and message metadata
- [ ] Customer deletion anonymizes message logs; order records retained per legal hold
- [ ] Primary message log storage in Nigeria/West Africa per ADR-011

## 12. Documentation & Traceability

- [ ] All 10 chapters published with stable document IDs SCP-AUT-001-01 through -10
- [ ] Cross-references to Volume 5, 11, 12 verified
- [ ] NFR-008, NFR-020, NFR-040, NFR-083 traceability matrix updated in README

---

## Phase Summary

| Phase | Volume 19 Completion |
|-------|---------------------|
| **Phase 1 Nigeria GA** | Sections 1–5, 9–12, CSV export (7.6), core WhatsApp/SMS |
| **Phase 2 Business+** | Sections 6–7, Zoho/QBO, marketing journeys |
| **Phase 3 Growth** | Section 8 marketplace partners, HubSpot, bi-directional inventory |

---

**Sign-off roles:** Lead Architect, Product Owner (Automation), DPO (Nigeria consent review), Security Lead, Integrations Engineering Lead.
