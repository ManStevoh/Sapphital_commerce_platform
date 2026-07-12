# Chapter 05: User Personas

Personas represent the primary user archetypes SCP serves. All UX, feature, and API decisions must consider impact on these personas.

---

## Persona 1: Amina — Solo Entrepreneur

```text
Age: 28
Location: Nairobi, Kenya
Business: Handmade jewelry and accessories
Revenue: KSh 80,000/month (~$620)
Channels: Instagram (primary), WhatsApp orders
Tech comfort: Uses smartphone fluently, no coding experience
Payment: M-Pesa personal number
```

### Goals

- Launch a professional online store without hiring a developer
- Accept M-Pesa payments automatically (stop manual confirmation)
- Look as professional as international brands on Instagram
- Spend less time on admin, more time creating products

### Frustrations

- Currently screenshots products on Instagram, takes orders via DM
- Manually confirms M-Pesa payments by checking phone messages
- Lost orders because DMs get buried
- Tried WooCommerce but found it too complex and hosting was confusing

### SCP Journey

1. Signs up via phone number + OTP
2. AI onboarding asks about her business, imports Instagram product photos
3. AI generates product titles, descriptions, and prices
4. Selects a free theme, previews on mobile
5. Connects M-Pesa Till Number via STK Push setup wizard
6. Store live at `amina-jewelry.sapphital.store` in 12 minutes
7. Shares link on Instagram bio
8. Receives first online order with automatic M-Pesa payment

### Key Features Used

- AI store setup, AI product descriptions
- M-Pesa STK Push
- Mobile-first theme
- Order notifications (SMS + push)
- Basic analytics (views, orders, revenue)

### Plan: Starter ($9/month)

---

## Persona 2: James — Growing SME Owner

```text
Age: 42
Location: Kampala, Uganda
Business: Electronics retail (phones, laptops, accessories)
Revenue: UGX 45M/month (~$12,000)
Employees: 8 (2 sales, 2 warehouse, 1 delivery, 1 admin, 2 shop floor)
Channels: Physical shop, WhatsApp, Jumia (considering leaving)
Tech comfort: Uses Excel, basic accounting software
Payment: MTN MoMo, bank transfer, some cash
```

### Goals

- Unify online and in-store inventory
- Reduce dependency on Jumia (high commissions)
- Track which products sell best across channels
- Manage delivery logistics efficiently
- Scale to second location

### Frustrations

- Inventory counts in Excel don't match physical stock
- Jumia takes 15%+ commission and controls customer relationship
- No single view of business performance
- Delivery coordination via phone calls is chaotic
- Can't offer installment payments easily

### SCP Journey

1. Migrates from Jumia with bulk product import (CSV + AI enrichment)
2. Sets up multi-location inventory (shop + warehouse)
3. Connects MTN MoMo and Airtel Money for online payments
4. Uses AI inventory forecasting to reorder best-sellers
5. Sets up delivery zones with local courier integration
6. Adds POS module for in-store sales (Phase 2)
7. Reviews weekly AI-generated business insights report

### Key Features Used

- Bulk import, multi-location inventory
- AI inventory forecasting
- Delivery zone management
- Advanced analytics dashboard
- Multi-payment gateway
- Staff accounts with role permissions

### Plan: Business ($49/month)

---

## Persona 3: Fatima — Marketplace Operator

```text
Age: 35
Location: Dar es Salaam, Tanzania
Business: "Soko Online" — multi-vendor fashion marketplace
Revenue: TZS 200M/month GMV (~$75,000)
Vendors: 120 active vendors
Employees: 15 (tech, support, marketing, operations)
Tech comfort: Technical co-founder, uses analytics tools
Payment: M-Pesa, Tigo Pesa, bank transfer
```

### Goals

- Onboard vendors easily with self-service portal
- Automate commission calculation and vendor payouts
- Maintain quality control across vendor listings
- Provide vendors with their own analytics
- Handle disputes and returns at scale

### Frustrations

- Current platform (custom PHP) crashes during peak sales
- Manual vendor payout reconciliation takes 3 days/month
- No vendor self-service — everything goes through her team
- Can't offer vendors customizable storefronts
- Security concerns with vendor data access

### SCP Journey

1. Enterprise onboarding with dedicated support
2. Migrates 120 vendors with automated invitation flow
3. Sets commission rates by category (10% fashion, 8% accessories)
4. Vendors get individual dashboards with sales analytics
5. Automated weekly payouts via M-Pesa bulk transfer
6. AI moderates new product listings for policy compliance
7. Customer dispute resolution workflow with vendor communication

### Key Features Used

- Multi-vendor marketplace mode
- Vendor portal with individual analytics
- Automated commission and payout engine
- AI content moderation
- Dispute resolution workflow
- Custom domain (sokoonline.co.tz)

### Plan: Marketplace ($199/month + 1.5% GMV commission)

---

## Persona 4: David — End Customer

```text
Age: 24
Location: Nairobi, Kenya
Occupation: Software developer
Income: KSh 120,000/month
Shopping behavior: Mobile-first, compares prices, reads reviews
Payment preference: M-Pesa (primary), card (secondary)
```

### Goals

- Find products quickly with natural language search
- Pay with M-Pesa without friction
- Track order delivery in real-time
- Return items easily if they don't match description
- Discover new products through recommendations

### Frustrations

- Most local online stores look untrustworthy
- Checkout flows that don't support M-Pesa
- No order tracking after purchase
- Can't find what he needs without knowing exact product names
- Slow-loading product pages on mobile data

### SCP Experience

1. Finds store via Instagram link
2. Searches "wireless earbuds under 3000" — AI understands intent
3. Views product page (loads in 1.2s on 4G)
4. Adds to cart, checks out with M-Pesa STK Push (2 taps)
5. Receives SMS confirmation with tracking link
6. AI chatbot answers delivery ETA question at 11 PM
7. Product arrives, leaves review with photo

### Key Features Experienced

- AI-powered search
- Fast mobile storefront
- M-Pesa checkout
- Order tracking
- AI support chatbot

---

## Persona 5: Grace — Developer / Agency Partner

```text
Age: 30
Location: Lagos, Nigeria
Role: Freelance developer + small agency (3 people)
Clients: 8 SME clients needing eCommerce
Tech stack: Laravel, React, Next.js
```

### Goals

- Build custom storefronts faster using SCP theme SDK
- Integrate client ERP/accounting via APIs
- Sell themes and plugins in the marketplace for passive income
- White-label SCP for agency clients
- Access comprehensive, Stripe-quality documentation

### Frustrations

- Every client project is built from scratch
- No African-focused commerce API to build on
- Shopify theme development requires Ruby/Liquid knowledge
- Documentation for local platforms is nonexistent
- Can't monetize reusable components

### SCP Journey

1. Discovers SCP developer documentation
2. Builds custom theme using Theme SDK (React + JSON templates)
3. Publishes theme to Theme Store ($49 one-time purchase)
4. Builds Paystack webhook integration plugin for client
5. Uses REST API to sync orders with client's accounting software
6. Earns 70% revenue share on theme sales

### Key Features Used

- Theme SDK and CLI
- Plugin SDK with hook system
- REST + GraphQL APIs
- Webhook system
- Developer documentation portal
- Sandbox environment

### Plan: Developer (free API access, revenue share on extensions)

---

## Persona Priority Matrix

| Persona | Phase 1 (MVP) | Phase 2 | Phase 3 | Revenue Impact |
|---------|--------------|---------|---------|----------------|
| Amina (Solo) | ✅ Primary | Enhanced AI | | Medium (volume) |
| James (SME) | Basic | ✅ Primary | POS | High |
| Fatima (Marketplace) | — | Basic | ✅ Primary | Very High |
| David (Customer) | ✅ Primary | Enhanced | | Indirect (GMV) |
| Grace (Developer) | — | SDK beta | ✅ Primary | Ecosystem |

---

## Persona-Driven Requirements

| ID | Requirement | Persona | Priority |
|----|-------------|---------|----------|
| FR-001 | Phone number + OTP registration | Amina | P0 |
| FR-002 | AI-assisted store setup wizard | Amina | P0 |
| FR-003 | M-Pesa STK Push checkout | Amina, David | P0 |
| FR-004 | Bulk product import (CSV) | James | P1 |
| FR-005 | Multi-location inventory | James | P1 |
| FR-006 | Vendor self-service portal | Fatima | P1 |
| FR-007 | Automated commission/payout | Fatima | P1 |
| FR-008 | AI natural language search | David | P0 |
| FR-009 | Order tracking page | David | P0 |
| FR-010 | Theme SDK with CLI | Grace | P2 |
| FR-011 | Plugin SDK with hook system | Grace | P2 |
| FR-012 | REST API with OpenAPI spec | Grace | P1 |
