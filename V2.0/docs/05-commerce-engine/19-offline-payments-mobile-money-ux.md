# Chapter 19: Offline Payments & Mobile-Money-First UX

**Document ID:** SCP-COM-005-19  
**Version:** 1.0.0  
**Status:** ✅ Active  
**Traceability:** ADR-019, FR-021, NFR-083  

---

## Purpose

Specify **offline payment workflows** and **mobile-money-first checkout UX** — reflecting how African commerce actually happens, not Western card-first defaults.

---

## 1. Mobile Money First

### UX Principle

Primary payment buttons:

```text
Pay with M-Pesa
Pay with Airtel Money
Pay with Mobile Money
```

Then: Bank transfer → Wallets → Cards.

### Storefront Requirements

| Element | Rule |
|---------|------|
| Payment method grid | Mobile money icons largest, first row |
| PDP trust strip | Show accepted mobile money brands for store country |
| One-tap STK | Phone number prefill from customer profile (consent) |
| Failure copy | "STK push declined" not generic "payment failed" |

### Admin

Merchant enables methods per FSL adapter; disabled methods hidden, not grayed (reduces confusion).

---

## 2. Offline Payment Methods

| Method | Customer Flow | Merchant Flow |
|--------|---------------|---------------|
| **Cash** | Select cash; order pending payment | Mark paid at POS or delivery |
| **Bank deposit** | Receive reference + account details | Confirm via webhook or manual |
| **Pay on delivery** | Order placed; pay courier/cashier | Confirm on delivery event |
| **Merchant confirmation** | Upload proof optional | Admin confirms with audit log |

### FSL States

```text
offline_pending → merchant_confirmed → paid
offline_pending → expired (TTL)
offline_pending → cancelled
```

All transitions append ledger notes; no silent state changes.

---

## 3. WhatsApp Commerce (Channel)

Full flow without leaving WhatsApp (extends Volume 19 Ch. 07):

```text
Customer chats → AI responds → catalog browse → order → FSL payment link/STK → receipt → track order
```

| Step | System |
|------|--------|
| Browse | WhatsApp catalog / deep link to storefront section |
| Order | Order API via conversation session |
| Pay | FSL payment link or M-Pesa STK via WhatsApp message |
| Receipt | WhatsApp utility template + PDF link |
| Track | Status templates on shipment events |

---

## 4. USSD (Future Channel)

Architecture allows USSD as **presentation channel** calling same Order + FSL APIs:

| Capability | Phase |
|------------|-------|
| Order status lookup | 3 |
| Payment reference retrieval | 3 |
| Simple account balance (wallet) | 4 |

Requires telecom partnerships per country; not Phase 1.

---

## 5. Acceptance Criteria

- [ ] Mobile money methods render before cards in all checkout themes
- [ ] Offline methods create auditable `PaymentIntent`
- [ ] Bank deposit generates unique reference per order
- [ ] WhatsApp payment deep link completes via FSL webhook
- [ ] USSD channel documented as extension point in API gateway

---

## References

- [Chapter 16 — Financial Services Layer](./16-financial-services-layer.md)
- [Volume 19 Ch. 07 — WhatsApp & SMS](../19-automation-integrations/07-whatsapp-sms-channels.md)
- [Volume 9 Ch. 16 — Africa Commerce AI](../09-ai-intelligence/16-africa-commerce-ai-advisor.md)
