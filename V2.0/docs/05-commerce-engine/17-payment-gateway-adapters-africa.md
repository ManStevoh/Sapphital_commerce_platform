# Chapter 17: Payment Gateway Adapters — Africa

**Document ID:** SCP-COM-005-17  
**Version:** 1.0.0  
**Status:** ✅ Active  
**Traceability:** ADR-019, FR-021, NFR-044  

---

## Purpose

Catalog **African payment providers** as FSL gateway adapters. Each implements the unified contract (Ch. 16). SCP markets **Africa support**, not a single global PSP.

**Global / international checkout:** Only **Stripe** and **PayPal** are supported outside Africa-first rails — not legacy 20+ gateway sprawl (Razorpay, Paytm, Midtrans, etc.). Add other global PSPs only via explicit ADR.

---

## 1. Adapter Registry

| Adapter ID | Provider | Countries | Primary Methods | Phase |
|------------|----------|-----------|-----------------|-------|
| `paystack` | Paystack | NG, GH, ZA, KE | Card, bank transfer, USSD, mobile money | 1 |
| `flutterwave` | Flutterwave | Pan-African | Card, bank, mobile money | 1 |
| `mpesa_ke` | Safaricom Daraja | KE | M-Pesa STK, C2B | 1b |
| `airtel_money_ke` | Airtel Money Kenya | KE | Mobile wallet | 2 |
| `pesalink` | Pesalink | KE | Bank transfer | 2 |
| `kcb` | KCB Bank | KE | Bank, mobile | 2 |
| `equity` | Equity Bank | KE | Bank, mobile | 2 |
| `dpo` | DPO Pay | Pan-African | Card, mobile money | 2 |
| `mtn_momo_ug` | MTN Mobile Money | UG | MoMo | 2 |
| `airtel_ug` | Airtel Money Uganda | UG | Mobile wallet | 2 |
| `mpesa_tz` | M-Pesa Tanzania | TZ | M-Pesa | 2 |
| `tigo_pesa` | Tigo Pesa | TZ | Mobile wallet | 2 |
| `halopesa` | HaloPesa | TZ | Mobile wallet | 2 |
| `mtn_momo_rw` | MTN MoMo Rwanda | RW | MoMo | 2 |
| `mtn_momo_gh` | MTN Mobile Money Ghana | GH | MoMo | 2 |
| `vodafone_cash` | Vodafone Cash | GH | Mobile wallet | 2 |
| `airteltigo_gh` | AirtelTigo Money | GH | Mobile wallet | 2 |
| `moniepoint` | Moniepoint | NG | POS, transfer (where API permits) | 2 |
| `opay` | Opay | NG | Wallet (API-dependent) | 3 |
| `payfast` | PayFast | ZA | Card, EFT, instant EFT | 3 |
| `peach` | Peach Payments | ZA | Card | 3 |
| `ozow` | Ozow | ZA | Instant EFT | 3 |
| `yoco` | Yoco | ZA | Card, tap | 3 |
| `paygate` | PayGate | ZA | Card | 3 |
| `cellulant` | Cellulant | Pan-African | Mobile money aggregator | 2 |
| `pesapal` | Pesapal | Pan-African | Mobile money, card | 2 |
| `stripe` | Stripe | International | Card, wallets | 2 |
| `paypal` | PayPal | International | PayPal balance, card via PayPal | 2 |

---

## 2. Country Launch Matrix

### Kenya 🇰🇪

| Provider | Adapter | Notes |
|----------|---------|-------|
| M-Pesa (Daraja) | `mpesa_ke` | STK Push primary UX |
| Airtel Money | `airtel_money_ke` | |
| Pesalink | `pesalink` | Bank-to-bank |
| KCB | `kcb` | Direct bank integration |
| Equity Bank | `equity` | |
| DPO Pay | `dpo` | Aggregator fallback |

### Uganda 🇺🇬

| Provider | Adapter |
|----------|---------|
| MTN Mobile Money | `mtn_momo_ug` |
| Airtel Money | `airtel_ug` |

### Tanzania 🇹🇿

| Provider | Adapter |
|----------|---------|
| M-Pesa Tanzania | `mpesa_tz` |
| Tigo Pesa | `tigo_pesa` |
| Airtel Money | `airtel_*` regional |
| HaloPesa | `halopesa` |

### Rwanda 🇷🇼

| Provider | Adapter |
|----------|---------|
| MTN MoMo | `mtn_momo_rw` |
| Airtel Money | Regional adapter |

### Ghana 🇬🇭

| Provider | Adapter |
|----------|---------|
| MTN Mobile Money | `mtn_momo_gh` |
| Vodafone Cash | `vodafone_cash` |
| AirtelTigo Money | `airteltigo_gh` |
| Paystack GH | `paystack` |

### Nigeria 🇳🇬

| Provider | Adapter |
|----------|---------|
| Paystack | `paystack` |
| Flutterwave | `flutterwave` |
| Moniepoint | `moniepoint` |
| Opay | `opay` |

### South Africa 🇿🇦

| Provider | Adapter |
|----------|---------|
| PayFast | `payfast` |
| Peach Payments | `peach` |
| Ozow | `ozow` |
| Yoco | `yoco` |
| PayGate | `paygate` |

### Pan-African

| Provider | Adapter | Role |
|----------|---------|------|
| Flutterwave | `flutterwave` | Multi-country aggregator |
| Paystack | `paystack` | NG + expansion markets |
| DPO Pay | `dpo` | East/Southern Africa |
| Cellulant | `cellulant` | Mobile money rails |
| Pesapal | `pesapal` | East Africa hub |

### Global (International — Stripe + PayPal only)

| Provider | Adapter | Notes |
|----------|---------|-------|
| Stripe | `stripe` | Cards, Apple Pay, Google Pay via hosted checkout |
| PayPal | `paypal` | PayPal wallet + card via PayPal Checkout |

No other global PSPs (Razorpay, Paytm, Midtrans, Mollie, etc.) unless approved by ADR.

---

## 3. Smart Routing Examples

```text
Customer in Nairobi + M-Pesa selected  → mpesa_ke
Customer in Lagos + card                 → paystack (or flutterwave by health score)
Customer in Johannesburg + EFT           → ozow or payfast
Customer in US + card                    → stripe
Customer in US/EU + PayPal account       → paypal
Customer in Kampala + Mobile Money       → mtn_momo_ug or cellulant
```

Merchant enables subset; router never selects disabled adapter.

---

## 4. Checkout Method Ordering (Mobile Money First)

API returns `payment_methods[]` sorted:

1. **Mobile money** — "Pay with M-Pesa", "Pay with MTN MoMo", "Pay with Mobile Money"
2. **Bank transfer / EFT**
3. **Wallets** (Opay, etc.)
4. **Cards**

Theme templates must not reorder without merchant override.

---

## 5. Adapter Implementation Checklist

Each adapter PR must include:

- [ ] Implements `PaymentGatewayAdapter` interface
- [ ] Sandbox credentials documented
- [ ] Webhook signature verification
- [ ] Idempotency key on `pay()`
- [ ] `capabilities()` accurate for countries/methods
- [ ] Integration test with recorded fixtures
- [ ] Runbook in Volume 21 ops playbook

---

## 6. Credential Storage

- Encrypted at rest (`gateway_configs.credentials_encrypted`)
- Per-tenant; platform master keys for sandbox only
- OAuth refresh for providers that support it
- Rotation without checkout downtime (blue/green config)

---

## References

- [Chapter 16 — Financial Services Layer](./16-financial-services-layer.md)
- [Chapter 18 — Regional Engines](./18-regional-engines-currency-tax-language.md)
- [Volume 2 Ch. 04 — Payment & Fintech Strategy](../02-market-research/04-payment-fintech-strategy.md)
