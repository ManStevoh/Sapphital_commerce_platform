# Chapter 11: Merchant Integrations Hub

**Document ID:** SCP-AUT-001-11  
**Version:** 1.0.0  
**Status:** ✅ Active  
**Traceability:** PRD-009, NFR-035, Vol 12 Ch. 08  
**Legacy mapping:** `Modules/Integrations` (GTM, Pixel, chat widgets)

---

## Purpose

Central **merchant-facing integrations panel** for third-party marketing, analytics, and chat scripts — without forking theme code.

## Scope

- Integration catalog and enable/disable per store
- Script injection rules (CSP-safe)
- Credential storage via `Platform/Secrets/`
- Connectors vs inline widgets

## Out of Scope

- Full ERP connectors (Ch. 06)
- Payment gateways (FSL)

---

## 1. Package Placement

`Modules/Extensions/Integrations/` or `Platform/Integrations/MerchantWidgets/`

Type: **extension** — requires Commerce.

---

## 2. Integration Catalog

| Integration | Type | Phase | Config fields |
|-------------|------|-------|---------------|
| **Google Tag Manager** | Script | 1 | Container ID |
| **Google Analytics 4** | Script | 1 | Measurement ID |
| **Meta Pixel** | Script | 1 | Pixel ID |
| **Google reCAPTCHA v3** | API | 1 | Site key + secret |
| **Cloudflare Turnstile** | API | 1 | Preferred over reCAPTCHA |
| **Tawk.to** | Script | 2 | Property ID |
| **Crisp** | Script | 2 | Website ID |
| **Tidio** | Script | 2 | Key |
| **Facebook Messenger** | Script | 3 | Page ID |
| **WhatsApp click-to-chat** | Link | 1 | Phone E.164 |
| **Instagram feed embed** | oEmbed | 2 | Handle |
| **AdRoll** | Script | 3 | Advertiser ID |

---

## 3. Injection Rules

```text
Storefront <head>   → GTM, GA4, Meta Pixel (async)
Storefront <body>   → Chat widgets (lazy on interaction)
Checkout            → Turnstile token only — no marketing pixels on payment step
Admin               → None (no third-party tracking in merchant admin)
```

**CSP:** nonce or allowlist domains per integration; violations logged.

---

## 4. Admin UI

**Settings → Integrations**

- Card per integration: logo, description, enable toggle, configure
- Test connection (where API-based)
- Preview: "Tags firing" checklist (Phase 2)

---

## 5. Security

- SSRF N/A for script IDs (validated format)
- No merchant-supplied raw JavaScript (Phase 1) — **ID-only config** into known templates
- Phase 3: reviewed custom scripts via developer app (Vol 12)

---

## 6. Events

- `IntegrationEnabled`, `IntegrationDisabled` → Audit

---

## 7. Acceptance Criteria

- [ ] GTM loads on storefront when enabled
- [ ] Checkout page excludes marketing pixels
- [ ] Turnstile on contact/checkout forms
- [ ] Disabled integration removes script within cache TTL
- [ ] Secrets not exposed in storefront HTML except public site keys

---

## References

- [Vol 4 Ch. 08 — Storefront UX](../04-design-system/08-storefront-and-checkout-ux.md)
- [Vol 12 Ch. 11 — SSRF](../12-developer-platform/11-security-ssrf-rate-limits.md)
