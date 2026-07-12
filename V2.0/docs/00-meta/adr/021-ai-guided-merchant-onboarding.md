# ADR-021: AI-Guided Merchant Onboarding

**Date:** 2026-07-12  
**Status:** Accepted  
**Deciders:** Stephen Musyoka Makola  
**Volume Reference:** Volume 16 Ch. 09; Volume 4 Ch. 15

## Context

Most platforms conflate **registration** with **onboarding** — sign up, verify email, empty store. Merchants ask *"What do I do next?"* SCP can beat Shopify here by treating onboarding as an **AI-guided business setup experience** that gets merchants selling quickly.

## Decision

Implement **seven-phase onboarding** orchestrated by **SAPPHITAL Intelligence** with **three segmented flows** by merchant tier.

### Seven Phases

```text
Discover → Register → Business Setup → Commerce Setup → Store Design → Go Live → Growth
```

### Three Flows

| Flow | Audience | Target time | Character |
|------|----------|-------------|-----------|
| **Starter** | Individuals, SMEs | ≤ 10 minutes | AI interview, minimal fields, auto-config |
| **Business** | Growing companies | ≤ 45 minutes | Teams, warehouses, integrations, migration |
| **Enterprise** | Large orgs | Sales-assisted | Workshops, pilot, migration, CSM, SSO |

### Core Mechanisms

1. **Pre-signup AI assistant** on marketing site — industry → theme/gateway recommendations
2. **AI Business Interview** — conversational setup, not 40-field forms
3. **Auto-provisioning** — country → currency, tax, language, gateways, theme, categories (Vol 5 Ch. 18)
4. **Readiness Score** — gamified % with actionable gaps (not just "Setup complete")
5. **AI Business Consultant** — one-click accept recommendations (M-Pesa, shipping thresholds, homepage)
6. **Post-launch Growth phase** — morning Copilot, Customer Success Center (never stop helping)

### Non-Blocking Verification

Business verification (CAC, tax ID) runs in **draft mode** — merchants build while verifying unless jurisdiction requires block.

## Alternatives Considered

| Alternative | Why Rejected |
|-------------|--------------|
| Single wizard for all tiers | Overwhelms SMEs; under-serves enterprise |
| Manual checklist only (Shopify-style) | Merchant asks "what next?" — poor Africa SMB UX |
| Block store until KYC complete | Kills time-to-first-sale for legitimate SMEs |

## Consequences

### Positive

- Differentiated go-to-market vs Shopify/WooCommerce
- Intelligence Platform proves value on day one
- Country-aware defaults reduce payment/tax misconfiguration

### Negative

- Onboarding state machine complexity
- AI misconfiguration requires easy undo
- Enterprise flow needs CRM/sales tooling Phase 2+

## References

- [Volume 16 Ch. 09](../../16-saas-multi-tenancy/09-ai-guided-merchant-onboarding.md)
- ADR-018, ADR-019, ADR-020
