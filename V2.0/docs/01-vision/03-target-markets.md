# Chapter 03: Target Markets

## Geographic Strategy

### Phase 1: West Africa — Nigeria Launch (Year 1)

**Primary:** Nigeria  
**Secondary:** Ghana  
**Parallel (East Africa):** Kenya — second launch market within Phase 1 when operational readiness is proven

| Factor | Nigeria Rationale |
|--------|-------------------|
| Population | 220M+ — largest economy and digital commerce market in Africa |
| SME density | 40M+ MSMEs (SMEDAN estimates) |
| Mobile money & fintech | Paystack, Flutterwave, OPay, PalmPay, bank transfers |
| eCommerce growth | Fastest-growing retail digitization in West Africa |
| English proficiency | Primary business language — reduces localization cost |
| Regulatory clarity | NDPA 2023 + GAID 2025 provide explicit compliance framework |

**Payment methods (Phase 1 — Nigeria):**

- Paystack (cards, bank transfer, USSD)
- Flutterwave (cards, bank transfer)
- OPay, PalmPay (wallet)
- Visa/Mastercard (via PSP hosted checkout)
- Bank transfer (NIBSS)

**Languages (Phase 1):**

- English (primary)
- Pidgin English (AI assistant — conversational UX)
- Hausa, Yoruba, Igbo (Phase 1.5 — storefront + AI)

**Compliance (Phase 1 — mandatory):**

- Nigeria Data Protection Act 2023 (NDPA)
- NDPC GAID 2025 (registration, DPO, CAR where applicable)
- PCI DSS SAQ A (hosted checkout)
- OWASP ASVS 5.0 Level 2

### Phase 1b: East Africa (Year 1–2)

**Primary:** Kenya  
**Secondary:** Tanzania, Uganda, Rwanda

| Factor | Kenya Rationale |
|--------|----------------|
| M-Pesa penetration | 90%+ of adult population |
| Mobile internet | 45M+ mobile internet users |
| SME density | 7.4M MSMEs (KNBS, 2024) |
| eCommerce growth | 25%+ YoY |

**Additional payment methods:**

- M-Pesa (STK Push, Paybill, Till Number)
- Airtel Money

**Additional compliance:**

- Kenya Data Protection Act 2019 + ODPC registration

### Phase 2: West & Central Africa Expansion (Year 2)

**Primary:** Ghana, Côte d'Ivoire, Senegal  
**Secondary:** South Africa (gateway to Southern Africa)

| Factor | Rationale |
|--------|-----------|
| ECOWAF/UEMOA | Shared payment rails via Flutterwave/Paystack |
| Francophone markets | French UI path validated |
| Naira/Cedi/others | Multi-currency architecture from day one |

**Additional payment methods:**

- MTN MoMo (Ghana)
- Mobile Money (Francophone West Africa)

**Additional languages:**

- French (West/Central Africa)

### Phase 3: Southern Africa + Global Emerging Markets (Year 3+)

- South Africa (ZAR, SnapScan)
- Egypt (Arabic, Fawry)
- Southeast Asia (similar emerging market dynamics)
- Latin America (similar mobile money patterns)

### Phase 4: Global (Year 4+)

- Full international payment support (Stripe global)
- Multi-region deployment (EU, US data residency)
- Enterprise tier with dedicated infrastructure

---

## Market Segments

### Segment A: Solo Entrepreneurs & Side Hustlers

| Attribute | Detail |
|-----------|--------|
| **Size** | 1 person |
| **Revenue** | $0 – $2,000/month |
| **Products** | 1 – 50 SKUs |
| **Channels** | Instagram, WhatsApp, online store |
| **Pain** | Need professional store without developer cost |
| **Plan** | Free / Starter ($0 – $9/month) |
| **Volume estimate** | 70% of merchant base |

### Segment B: Growing SMEs

| Attribute | Detail |
|-----------|--------|
| **Size** | 2 – 20 employees |
| **Revenue** | $2,000 – $50,000/month |
| **Products** | 50 – 5,000 SKUs |
| **Channels** | Online + social + possibly physical |
| **Pain** | Outgrowing manual processes; need inventory, analytics, automation |
| **Plan** | Business ($29 – $79/month) |
| **Volume estimate** | 25% of merchant base |

### Segment C: Marketplace Operators

| Attribute | Detail |
|-----------|--------|
| **Size** | 5 – 50 employees |
| **Revenue** | $10,000 – $500,000/month |
| **Vendors** | 10 – 1,000 vendors |
| **Channels** | Multi-vendor marketplace + individual vendor stores |
| **Pain** | Vendor management, commissions, payouts, dispute resolution |
| **Plan** | Marketplace ($99 – $299/month + commission) |
| **Volume estimate** | 4% of merchant base |

### Segment D: Enterprise

| Attribute | Detail |
|-----------|--------|
| **Size** | 50+ employees |
| **Revenue** | $500,000+/month |
| **Products** | 5,000+ SKUs |
| **Requirements** | Custom integrations, SLA, dedicated support, data residency |
| **Plan** | Enterprise (custom pricing) |
| **Volume estimate** | 1% of merchant base, 15%+ of revenue |

---

## Tenant Tiers

| Tier | Database Isolation | Custom Domain | API Rate Limit | Storage | Support |
|------|-------------------|---------------|----------------|---------|---------|
| Free | Shared + RLS | Subdomain only | 100 req/min | 1 GB | Community |
| Starter | Shared + RLS | Subdomain | 500 req/min | 5 GB | Email |
| Business | Shared + RLS | Custom domain | 2,000 req/min | 50 GB | Priority email |
| Marketplace | Shared + RLS | Custom domain | 5,000 req/min | 200 GB | Dedicated |
| Enterprise | Schema-per-tenant | Custom domain | Custom | Custom | SLA + phone |

---

## Total Addressable Market (TAM)

| Metric | Value | Source |
|--------|-------|--------|
| African eCommerce market (2028) | $75B | Statista |
| SaaS commerce platform segment | ~$3.5B (global) | Gartner |
| Addressable African SME merchants | ~15M | IFC/World Bank |
| Serviceable (English + digital-ready) | ~3M | Estimate |
| Initial serviceable (Kenya + East Africa) | ~500K | KNBS + regional data |

**Revenue model at scale (Year 5 target):**

| Source | Assumption | Annual Revenue |
|--------|-----------|----------------|
| Subscriptions (50K merchants × $25 avg/month) | 3% conversion of serviceable | $15M |
| Transaction fees (0.5% of GMV) | $500M GMV | $2.5M |
| Marketplace commission (2% of vendor GMV) | $100M vendor GMV | $2M |
| Theme/plugin marketplace (30% share) | $5M ecosystem GMV | $1.5M |
| Enterprise contracts | 10 clients × $50K/year | $0.5M |
| **Total** | | **~$21.5M** |

---

## Go-to-Market Strategy

### Phase 1: Kenya Launch

1. **Beta program** — 50 hand-selected merchants (free Business tier for 6 months)
2. **Content marketing** — Swahili + English tutorials, YouTube, TikTok
3. **Partnership** — Safaricom developer ecosystem, Paystack Kenya
4. **Community** — WhatsApp merchant community, local meetups
5. **AI differentiation** — "Launch your store in 10 minutes with AI" campaign

### Phase 2: East Africa Expansion

1. Localized payment methods per country
2. Local language support
3. Country-specific logistics integrations
4. Partner/reseller program for agencies

### Phase 3: Developer Ecosystem

1. Theme Store launch
2. Plugin marketplace
3. Developer documentation portal (Volume 12)
4. Hackathons and developer grants

---

## Requirements

| ID | Requirement | Priority |
|----|-------------|----------|
| PRD-011 | Platform must support multi-currency with real-time exchange rates | P0 |
| PRD-012 | Platform must support M-Pesa STK Push as primary payment method | P0 |
| PRD-013 | Platform UI must support English and Swahili at launch | P1 |
| PRD-014 | Platform must support tenant subscription tiers with feature gating | P0 |
| PRD-015 | Platform architecture must support geographic expansion without re-architecture | P0 |
