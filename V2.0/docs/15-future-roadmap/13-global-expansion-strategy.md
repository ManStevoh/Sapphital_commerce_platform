# Chapter 06: Global Expansion Strategy

**Document ID:** SCP-ROAD-001-06  
**Version:** 1.0.0  
**Status:** ✅ Active  
**Traceability:** ADR-011, NFR-083, NFR-084, NFR-085

---

## Purpose

Define SCP **geographic expansion strategy** beyond Nigeria — sequencing corridors, localization, payments, compliance, and infrastructure per region.

## Scope

- Expansion corridors (West, East, Southern Africa)
- Per-region launch requirements
- Localization and currency
- Payment rail mapping
- Data residency options
- Go-to-market partnerships

## Out of Scope

- North America / EU full launch (enterprise-only Phase 5)
- Localization of all marketing content
- Embassy/trade treaty negotiations

---

## 1. Expansion Principles

1. **Prove in Nigeria first** — 99.9% SLO, NDPA compliance, unit economics.
2. **Corridor clustering** — West Africa → East Africa → Southern Africa.
3. **Regulatory gate per country** — No "soft launch" without DPA review.
4. **Regional payment rails** — Local PSP before cross-border only.
5. **Infrastructure follows tenants** — Deploy region when ≥ 100 paying merchants forecast.

---

## 2. Corridor Roadmap

| Corridor | Countries | Target Horizon | Primary PSP |
|----------|-----------|----------------|-------------|
| **Nigeria (home)** | NG | H1 2026 | Paystack, Flutterwave |
| **East Africa** | KE, UG, TZ | H2 2027 | M-Pesa, Paystack KE |
| **West Africa** | GH, CI, SN | H3 2028 | Paystack GH, Hubtel |
| **Southern Africa** | ZA, BW | H4 2029 | Peach, Paystack ZA |
| **EU enterprise** | DE, NL (GDPR) | H5 2030 | Stripe (enterprise cell) |

```mermaid
map
    title SCP Expansion Corridors
    Nigeria : H1 Foundation
    Kenya : H2 East Africa
    Ghana : H3 West Africa
    South Africa : H4 Southern Africa
```

---

## 3. Per-Region Launch Checklist

| Requirement | Nigeria | Kenya | Ghana | South Africa |
|-------------|---------|-------|-------|--------------|
| DPA registration | NDPC | ODPC | DPC Ghana | POPIA |
| Local entity | Sapphital NG | Partner/branch | Partner | Partner |
| Data residency | Lagos | Nairobi | Lagos fallback → local Phase 2 | Johannesburg |
| Currency | NGN | KES | GHS | ZAR |
| Tax display | VAT if registered | VAT 16% | VAT 15% | VAT 15% |
| Support hours | WAT | EAT | WAT/GMT | SAST |

---

## 4. Localization

| Layer | Phase 1 | Phase 3 |
|-------|---------|---------|
| Admin UI | English | English + French (West Africa) |
| Storefront | Merchant content | Platform checkout strings localized |
| Currency format | Per region | Auto by tenant `region` |
| Phone validation | Per country regex | |
| Address forms | Nigeria states | KE counties, GH regions |

---

## 5. Payment Rail Matrix

| Country | Cards | Mobile Money | Bank Transfer | USSD |
|---------|-------|--------------|---------------|------|
| Nigeria | ✅ | — | ✅ | ✅ |
| Kenya | ✅ | M-Pesa ✅ | ✅ | — |
| Ghana | ✅ | MTN MoMo ✅ | ✅ | — |
| South Africa | ✅ | — | EFT ✅ | — |

SCP integrates via PSP aggregators to reduce N× integrations.

---

## 6. Infrastructure Strategy

| Region | Phase | Topology |
|--------|-------|----------|
| West Africa | H1–H3 | Lagos primary; Ghana edge cache |
| East Africa | H2+ | Nairobi regional stack (Volume 3 Ch. 12) |
| Southern Africa | H4 | Cape Town / Johannesburg evaluation |
| EU | H5 | Dedicated GDPR cell; no mixed tenancy |

Cross-region analytics: async export only.

---

## 7. Partnership GTM

| Partner Type | Role |
|--------------|------|
| Payment PSP | Co-marketing, subsidized onboarding |
| Logistics | GIG, Kwik Nigeria; Sendy Kenya |
| Telcos | MTN Ghana SME bundles |
| Agencies | Certified SCP implementers |
| DFIs / accelerators | Portfolio merchant packages |

---

## 8. Risk Register (Expansion)

| Risk | Mitigation |
|------|------------|
| FX volatility | Price in local currency; review quarterly |
| Regulatory change | Legal retainer per corridor |
| PSP outage | Dual PSP per country |
| Low ARPU markets | Starter plan tiering per corridor |

---

## 9. Acceptance Criteria

- [ ] Five corridors with horizons and PSPs
- [ ] Per-region checklist: DPA, residency, currency, tax
- [ ] Kenya ODPC and M-Pesa in East Africa pack
- [ ] Localization phases documented
- [ ] Infrastructure regional stack cross-reference
- [ ] Partnership GTM types listed
- [ ] Nigeria must prove SLO before each new corridor

---

## References

- [ADR-011 — Data Residency](../00-meta/adr/011-data-residency-africa.md)
- [Volume 11 Ch. 02 — Africa Compliance](../11-security/02-africa-regulatory-compliance.md)
- [Volume 5 Ch. 08 — Payments](../05-commerce-engine/08-payments-nigeria-africa.md)
- [Chapter 07 — Enterprise Features](./07-enterprise-features-roadmap.md)
