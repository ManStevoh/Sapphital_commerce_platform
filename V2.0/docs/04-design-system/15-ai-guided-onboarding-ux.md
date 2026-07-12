# Chapter 15: AI-Guided Onboarding UX

**Document ID:** SCP-DS-001-15  
**Version:** 1.0.0  
**Status:** ✅ Active  
**Traceability:** ADR-021, PRD-001, PRD-002, Product Principles 2, 3, 6  

---

## Purpose

Define **UX patterns** for SAPPHITAL's AI-guided onboarding — landing discovery, conversational interview, readiness score, go-live moment, and post-launch growth surfaces. Beats Shopify's empty-dashboard model.

---

## 1. Design Principles

| Principle | Implementation |
|-----------|----------------|
| Never "what next?" | Persistent next-action chip + progress rail |
| Conversation over forms | Chat-first interview; forms only for verification docs |
| Show, don't tell | Live store preview updates as AI configures |
| Mobile-first | Full flow on 320px; thumb-zone CTAs |
| Celebrate progress | Readiness score, confetti on launch (respect reduced motion) |

---

## 2. Pre-Signup Discovery

### Landing page

| Section | UX note |
|---------|---------|
| Hero | Primary CTA "Start Free"; secondary "Book Demo" |
| AI demo | Embedded assistant widget, bottom-right |
| Industry solutions | Card grid → deep link with `?vertical=` |
| Live examples | Real storefront screenshots, not lorem |

### Anonymous AI assistant widget

- Minimized: pill "Ask about your business"
- Expanded: chat panel 400×560 desktop; full-screen sheet mobile
- No email required; session ID in cookie
- Handoff banner after signup: "Continue where you left off"

---

## 3. Registration Screen

```text
┌─────────────────────────────────┐
│  [G] [Microsoft] [Apple] [GitHub]│
│  ───────── or email ─────────── │
│  Business name ________________ │
│  [ Continue ]                   │
└─────────────────────────────────┘
```

- Single field + OAuth — max 2 taps to continue
- Business name editable later
- No plan selection blocking (default Starter trial)

---

## 4. AI Business Interview

### Layout (desktop)

```text
┌──────────────┬────────────────────────────┐
│ Live preview │ Conversation               │
│ (store)      │ AI messages + quick replies│
│              │ [Type or speak...]         │
│  updates     │ Progress: Step 2 of 5      │
│  in realtime │                            │
└──────────────┴────────────────────────────┘
```

### Layout (mobile)

Tab switch: **Chat** | **Preview** — preview shows config changes after each answer.

### Quick reply chips

`Yes` · `No` · `Kenya` · `Nigeria` · `I have products` · `Import website`

Reduces typing on mobile.

---

## 5. Commerce Setup Progress

Horizontal stepper (collapsible on mobile):

```text
● Products  ○ Inventory  ○ Payments  ○ Shipping  ○ Policies
```

Each step: AI summary at top + manual override link ("Edit manually").

### Product import modal

Tabbed: Upload CSV · Import Shopify · AI Generate · Manual

Drag-drop zone; AI cleanup progress bar with item count.

---

## 6. Theme Selection UX

Not a grid of 300 themes.

```text
What kind of business?  [Fashion] [Electronics] [Pharmacy] ...
```

Then card row of **3 themes** with badges:

- ⭐ Most popular · ⚡ Fastest · 📈 Highest conversion · 🤖 AI pick

Each card: mobile + desktop thumbnail, vertical tags, "Preview" CTA.

---

## 7. Readiness Score Component

```text
┌─────────────────────────────────────┐
│  Store Readiness            92%     │
│  ████████████████████░░░░           │
│  ✓ Theme  ✓ Payments  ✓ Products    │
│  ⚠ Privacy policy — [Fix now]       │
│  ⚠ Custom domain — [Connect]        │
│  [ Launch store ]  (primary)        │
└─────────────────────────────────────┘
```

| Token | Use |
|-------|-----|
| `readiness-complete` | Green check |
| `readiness-warning` | Amber; actionable link |
| `readiness-blocked` | Red; launch disabled |

Animate score increment (+3%) on step complete — subtle, not slot-machine.

---

## 8. Go Live Moment

Full-screen modal (skippable):

- Store URL prominent with copy button
- Share: WhatsApp, copy link, QR code
- **Launch Now** / Preview / Schedule (date picker)

Schedule: countdown on admin home until launch hour.

---

## 9. Post-Launch Growth Home

Admin home **before** traditional dashboard widgets:

```text
┌─ Good morning, Stephen ─────────────────┐
│ Yesterday ↑18% · Orders 124 · Alerts 3  │
│ Today's suggestions (AI)                │
│  • Enable M-Pesa Express    [Apply]     │
│  • Add FAQ page             [Start]     │
└─────────────────────────────────────────┘
```

"Apply" triggers one-click agent actions (with confirm).

Customer Success Center: sidebar item with checklist %, videos, AI coach entry.

---

## 10. Enterprise Onboarding Portal (Phase 2)

Separate route `/enterprise/onboarding` — milestone timeline, assigned tasks per stakeholder, document upload, workshop scheduling.

Not shown to Starter merchants.

---

## 11. Accessibility

- Interview chat: WCAG 2.2 AA; live region for AI responses
- Readiness score: not color-only (icons + text)
- OAuth buttons: accessible names
- Keyboard: Tab through quick replies

---

## 12. Acceptance Criteria

- [ ] Registration ≤ 2 fields visible at once
- [ ] Interview completable on 375px width
- [ ] Readiness component in Storybook with all states
- [ ] Launch moment meets reduced-motion variant
- [ ] Pre-signup assistant hands off to signed-up session

---

## References

- [Volume 16 Ch. 09](../16-saas-multi-tenancy/09-ai-guided-merchant-onboarding.md)
- [Ch. 07 — Admin Dashboard UX](./07-admin-and-merchant-dashboard-ux.md)
