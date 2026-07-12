# Chapter 04: Product Principles

Product principles translate engineering principles into product-level decision frameworks. When two features compete for priority, these principles determine the winner.

---

## Principle 1: Speed Is a Feature

Every interaction must feel instant. Perceived performance matters as much as measured performance.

**Rules:**

- Skeleton screens during loading — never blank pages
- Optimistic UI updates for cart, wishlist, settings changes
- Prefetch product data on hover/viewport entry
- API responses streamed where possible
- Admin actions provide immediate feedback, process async when heavy

**Anti-patterns to reject:**

- Full page reloads for filter changes
- Spinners without progress indication for operations > 1 second
- Blocking modals during background operations

---

## Principle 2: Clarity Over Cleverness

Every screen answers three questions instantly:

1. Where am I?
2. What can I do here?
3. What happens if I click this?

**Rules:**

- One primary action per screen section
- Labels over icons alone (icons supplement, not replace)
- Error messages explain what went wrong AND how to fix it
- Empty states guide the user to their first action
- Confirmation dialogs only for destructive/irreversible actions

---

## Principle 3: Mobile Is Primary, Not Secondary

60%+ of African eCommerce traffic is mobile. Design mobile-first, enhance for desktop.

**Rules:**

- Touch targets minimum 44×44px
- Bottom navigation for mobile admin (thumb-reachable)
- Responsive breakpoints: 320px, 768px, 1024px, 1440px
- Mobile checkout must complete in ≤ 3 taps after cart
- Test all flows on 320px width and 3G throttled connection

---

## Principle 4: AI Is the Intelligence Layer

AI is not a chatbot feature — it is the platform brain. Every module should expose how Intelligence makes it faster, smarter, or easier.

**Rules:**

- Business apps call **SAPPHITAL Intelligence** via capability APIs — never LLM SDKs directly
- AI suggestions appear contextually (product form → generate description) **and** proactively (morning Copilot briefing)
- All AI-generated content is editable before publishing
- Irreversible operations require explicit confirmation
- Recommendations include **explanation** and internal **confidence** scores
- Merchants can disable AI per module; platform defaults favor assistive, not intrusive, automation

---

## Principle 5: Local First, Global Ready

Default experiences optimize for African merchants. Global features are available but not default.

**Rules:**

- Default currency: tenant's country currency (NGN for Nigeria, KES for Kenya)
- Default payment methods: local mobile money first, cards second
- Default shipping: local couriers, pickup points, boda-boda
- Phone number as primary identity (not just email)
- Date/time in local timezone with 24-hour format option

---

## Principle 6: Progressive Disclosure

Show simple defaults. Reveal complexity only when the user needs it.

**Rules:**

- Product creation: title + price + photo → "Advanced options" collapsed
- Settings organized by frequency of use (common settings first)
- Admin navigation: 5–7 top-level items, sub-navigation for detail
- Onboarding wizard: 3 steps to launch, advanced config later
- Feature discovery through contextual tips, not upfront tutorials

---

## Principle 7: Trust by Design

Customers must feel safe purchasing from any SCP store.

**Rules:**

- SSL on every store (automatic via platform)
- Payment provider logos visible at checkout
- Order confirmation via SMS + email
- Real-time order tracking page
- Return/refund policy displayed before purchase
- Merchant verification badges for marketplace vendors

---

## Principle 8: Consistency Across Surfaces

Admin, vendor portal, storefront, and mobile must feel like one product.

**Rules:**

- Single design system (Volume 4) used across all surfaces
- Same interaction patterns for equivalent actions (save, delete, filter)
- Same terminology (see Glossary)
- Same notification style and placement
- Same error handling patterns

---

## Principle 9: Data-Informed, Not Data-Driven

Metrics guide decisions but do not override user needs.

**Rules:**

- A/B test significant UX changes before full rollout
- Merchant analytics dashboard included from Business tier
- Platform analytics inform roadmap, not individual merchant experience
- Privacy: merchant data belongs to merchant; platform aggregates anonymized

---

## Principle 10: Accessible to All

Commerce must work for users with disabilities, slow connections, and older devices.

**Rules:**

- WCAG 2.2 AA compliance on all surfaces
- Keyboard navigation for all admin workflows
- Screen reader tested for checkout flow
- Color contrast ratio ≥ 4.5:1 for text
- Works on Android 10+ and iOS 15+ browsers
- Graceful degradation on 3G (text content loads even if images deferred)

---

## Decision Framework

When evaluating any product decision, score against these principles:

| Question | Weight |
|----------|--------|
| Does this improve speed/perceived performance? | High |
| Is it clear what this does and why? | High |
| Does it work on mobile (320px, 3G)? | High |
| Does it work for African payment/logistics reality? | High |
| Is AI optional and contextual? | Medium |
| Does Intelligence accelerate this workflow? | High |
| Is complexity hidden until needed? | Medium |
| Does it build trust? | High |
| Is it consistent with the design system? | High |
| Is it accessible (WCAG 2.2 AA)? | High |
| Can we measure its impact? | Medium |

**Scoring:** Features scoring "High" on ≥ 7 principles proceed. Features failing any High-weight principle require explicit ADR justification.
