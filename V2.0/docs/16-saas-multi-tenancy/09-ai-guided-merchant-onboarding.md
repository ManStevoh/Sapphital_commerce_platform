# Chapter 09: AI-Guided Merchant Onboarding

**Document ID:** SCP-SAAS-001-09  
**Version:** 1.0.0  
**Status:** ✅ Active  
**Traceability:** ADR-021, ADR-020, PRD-001, PRD-002, PRD-003  

---

## Purpose

Define onboarding as an **AI-guided business setup experience** — not registration. The merchant never asks *"What do I do next?"* A dedicated onboarding consultant (Intelligence Platform) is available 24/7.

> **Registration:** sign up + verify email. **Onboarding:** get selling.

---

## 1. Philosophy

| Registration (competitors) | SAPPHITAL onboarding |
|---------------------------|----------------------|
| Empty admin dashboard | Usable draft store after interview |
| Merchant discovers features | AI guides next best action |
| One flow for everyone | Starter / Business / Enterprise |
| Setup complete = done | Go Live → Growth phase begins |

---

## 2. Seven Phases

```mermaid
flowchart LR
    D[Discover] --> R[Register]
    R --> B[Business Setup]
    B --> C[Commerce Setup]
    C --> S[Store Design]
    S --> L[Go Live]
    L --> G[Growth]
```

| Phase | Goal | AI role |
|-------|------|---------|
| **1. Discover** | Explore before account | Pre-signup assistant; industry recommendations |
| **2. Register** | Minimal friction | OAuth + business name only |
| **3. Business Setup** | AI interview → auto-config | Interview agent; provision store |
| **4. Commerce Setup** | Products, payments, shipping, tax | Guided steps + import tools |
| **5. Store Design** | Theme, brand, homepage | Theme Designer agent |
| **6. Go Live** | Launch event | Readiness score gate |
| **7. Growth** | Post-launch success | Copilot, Customer Success Center |

---

## 3. Three Onboarding Flows

### 3.1 Starter (Individuals & SMEs)

| Attribute | Value |
|-----------|-------|
| Target | First sale in ≤ 10 minutes (stretch ≤ 45 min full setup) |
| Auth | Google, Microsoft, Apple, Email |
| Required at signup | Business name only |
| Verification | Optional during build; required before live payouts (country rules) |
| Features | AI interview, auto-config, top-3 theme picks, gateway recommend |

### 3.2 Business (Growing Companies)

| Attribute | Value |
|-----------|-------|
| Target | Full setup ≤ 45 minutes with team |
| Extra steps | Team invites, multi-warehouse, accounting hooks, approval workflows |
| Migration | Shopify, WooCommerce, CSV, API import |
| Analytics | Advanced readiness items (SEO, policies, domain) |

### 3.3 Enterprise

```text
Request Demo → Sales Qualification → Solution Design → Technical Workshop
  → Pilot → Migration → Training → Production → Customer Success
```

| Attribute | Value |
|-----------|-------|
| Sales-assisted | Dedicated CSM, solution architect |
| Governance | SSO, security review, compliance workshop |
| Migration | Planned cutover, parallel run, rollback plan |
| Go-live | Formal readiness assessment sign-off |

---

## 4. Phase 1 — Discovery (Pre-Signup)

### Landing page sections

Hero · Features · Live store examples · AI demo · Industry solutions · Pricing · Customer stories · FAQ · Book enterprise demo

**Primary CTAs:** Start Free · Book Enterprise Demo · Watch Live Demo · Explore Templates

### Pre-signup AI Website Assistant

Anonymous session (no account):

> "Hi! What kind of business are you starting?"

| Industry | AI recommends |
|----------|---------------|
| Restaurant | Chop & Serve theme, hours, delivery radius |
| Pharmacy | Pharmacy vertical theme, compliance modules |
| Electronics | Terminal Tech, spec sections, comparison |
| School | Academy Path, fees, uniforms |
| Fashion | Lagos Atelier, lookbook sections |

Outputs stored in session cookie → pre-fill onboarding interview after signup.

---

## 5. Phase 2 — Registration

**Fields at signup:** Business name + auth provider. Nothing else.

| Provider | Phase |
|----------|-------|
| Google | 1 |
| Microsoft | 1 |
| Apple | 1 |
| GitHub | 1 (developer segment) |
| Email + password | 1 |

Email verification: async — do not block AI interview (send link in background).

---

## 6. Phase 3 — AI Business Interview

Conversational agent replaces 40 manual settings.

```text
AI: Welcome Stephen. Let's build your business. What do you sell?
Merchant: Electronics
AI: Which country?
Merchant: Kenya
AI: Do you have products already? / Existing website to import?
```

### Auto-provisioned from answers

| Setting | Source |
|---------|--------|
| Currency, timezone, language | Country (Ch. 18 regional engines) |
| Tax profile | Country VAT/GST |
| Payment gateways (recommended) | FSL adapter catalog |
| Default theme + homepage structure | Vertical + country |
| Categories, navigation | Vertical template |
| Shipping defaults | Country couriers |
| AI personality / prompts | Vertical tone |

**SLA:** Draft store usable within **60 seconds** after interview completes — executed by **Tenant Provisioning Engine** ([Ch. 10](./10-tenant-provisioning-engine.md), ADR-022).

### Business verification (non-blocking)

Collect when needed: registration number, tax ID, address, documents.

- **Draft mode:** build and preview while verifying
- **Block live payments** only when regulation requires verified merchant

---

## 7. Phase 4 — Commerce Setup

Progress-tracked substeps:

```text
Products → Inventory → Payments → Shipping → Taxes → Policies
```

### Product import options

| Method | Phase |
|--------|-------|
| Manual entry | 1 |
| CSV / Excel upload | 1 |
| AI generate products | 1 |
| Shopify import | 2 |
| WooCommerce import | 2 |
| API import | 2 |

AI cleans: names, descriptions, images, categories, SEO.

---

## 8. Phase 5 — Store Design

### Theme selection (not 300 themes)

Ask vertical → show **Top 3**:

- Most popular · Fastest · Highest conversion · **AI recommendation**

### Theme personalization

```text
Upload logo → extract brand colors → typography → banners → homepage → favicon → navigation
```

Theme Designer agent (Volume 9); merchant approves before publish.

---

## 9. Phase 6 — Payment & Shipping

Country-aware FSL recommendations:

| Country | Recommended |
|---------|-------------|
| Kenya | M-Pesa, Airtel Money, Pesapal |
| Nigeria | Paystack, Flutterwave |
| South Africa | Peach, Ozow, Yoco |

Shipping interview:

```text
Local only? National? International?
→ Courier integrations · Pickup · Flat rate · Free shipping rules
```

---

## 10. Store Readiness Score

Replace binary "Setup complete" with gamified score:

```text
Store Readiness: 92%

✓ Theme  ✓ Payments  ✓ Shipping  ✓ Products  ✓ SEO
⚠ Privacy Policy  ⚠ Logo  ⚠ Custom Domain
```

| Score band | UX |
|------------|-----|
| ≥ 90% | Launch unlocked |
| 70–89% | Launch with warnings |
| < 70% | Block launch; show top 3 fixes |

Readiness computed from weighted checklist (configurable per flow).

### Go Live

```text
Congratulations! Your store is ready.
[ Launch Now ] [ Preview ] [ Schedule Launch ]
```

Launch event: optional share, WhatsApp announce template, analytics `StoreLaunched`.

---

## 11. Phase 7 — Growth (Post-Launch)

Onboarding **does not end** at launch.

### Morning dashboard (Commerce Copilot)

```text
Good morning, Stephen.
Yesterday: Revenue KES 12,400 · Orders 16 · Visitors 421
Suggestions: Enable M-Pesa Express · Add FAQ · Facebook campaign · Optimize images
```

### Customer Success Center

| Resource | Purpose |
|----------|---------|
| Interactive checklist | Ongoing adoption |
| Video tutorials | Self-serve |
| AI coach | Same onboarding agent, growth mode |
| Documentation | Volume-linked help |
| Community forum | Phase 2 |
| Support | Ticket + WhatsApp |
| Health score | Churn prevention |

---

## 12. AI Business Consultant (Day One)

Example:

> "Welcome, Stephen. I see you're opening an electronics store in Kenya. Based on similar businesses, I recommend enabling M-Pesa, free shipping above KSh 5,000, and highlighting laptops on your homepage. Configure that for you?"

**[ Yes ]** → agents execute with audit trail · **[ Customize ]** → interview subset · **[ No ]** → skip

---

## 13. Onboarding State Machine

```text
discover_session → registered → interview_in_progress → interview_complete
  → commerce_setup → design_setup → readiness_review → live → growth
```

Enterprise parallel track: `enterprise_qualified → workshop → pilot → production`.

Persisted on `tenants.onboarding_state` + `tenants.readiness_score`.

---

## 14. Events

| Event | Consumers |
|-------|-----------|
| `OnboardingInterviewCompleted` | Provisioning, Intelligence |
| `ReadinessScoreChanged` | Admin UI, analytics |
| `StoreLaunched` | Billing activation, success metrics |
| `OnboardingRecommendationAccepted` | Agent audit, analytics |

---

## 15. Acceptance Criteria

- [ ] Starter: business name + OAuth → draft store ≤ 60s after interview
- [ ] Pre-signup AI assistant works without account
- [ ] Kenya/Nigeria auto-config currency, tax, gateways
- [ ] Readiness score displayed with weighted checklist
- [ ] Launch blocked below threshold (configurable)
- [ ] Enterprise flow documented with CSM milestones
- [ ] Post-launch Copilot briefing on day 1 after launch
- [ ] Three flows route by plan selection + self-segmentation

---

## References

- [ADR-021](../00-meta/adr/021-ai-guided-merchant-onboarding.md)
- [Volume 4 Ch. 15 — Onboarding UX](../04-design-system/15-ai-guided-onboarding-ux.md)
- [Volume 5 Ch. 18 — Regional Engines](../05-commerce-engine/18-regional-engines-currency-tax-language.md)
- [Volume 9 Ch. 22 — Commerce Copilot](../09-ai-platform/22-advanced-ai-capabilities.md)
- [Volume 21 Ch. 10 — Implementation Journeys](../21-implementation-playbooks/10-onboarding-user-journeys.md)
