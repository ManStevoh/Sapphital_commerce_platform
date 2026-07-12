# Chapter 09: Accessibility (WCAG 2.2 AA)

**Document ID:** SCP-DS-001-09  
**Version:** 1.0.0  
**Status:** ✅ Active  
**Traceability:** NFR-047 – NFR-053, Volume 11  

---

## 1. Purpose

Define accessibility requirements and implementation standards for SDS. SCP targets **WCAG 2.2 Level AA** on all surfaces — admin, merchant dashboard, vendor portal, storefront, and checkout. Accessibility is a Phase 1 launch requirement, not a retrofit.

## 2. Regulatory & Standards Alignment

| Standard | Level | Verification |
|----------|-------|--------------|
| WCAG 2.2 | AA | Automated + manual audit |
| EN 301 549 | Aligned via WCAG 2.2 AA | Phase 3 EU |
| Nigeria NDPA | Accessibility as reasonable accommodation | Phase 1 |
| Section 508 | Aligned via WCAG 2.2 AA | US enterprise Phase 3 |

Volume 11 establishes WCAG 2.2 AA as the security/compliance baseline; this chapter provides implementation guidance.

## 3. NFR Mapping

| NFR | Requirement | SDS Implementation |
|-----|-------------|-------------------|
| NFR-047 | WCAG 2.2 AA | This chapter; axe-core CI |
| NFR-048 | Keyboard navigation | §5; all admin workflows |
| NFR-049 | Screen reader checkout | §6; NVDA + VoiceOver tested |
| NFR-050 | Color contrast ≥ 4.5:1 / 3:1 | Token design (Chapter 02) |
| NFR-051 | Touch targets ≥ 44px | Component specs (Chapter 06) |
| NFR-052 | Reduced motion | Motion system (Chapter 04) |
| NFR-053 | Form accessibility | §7; labels, errors, focus |

## 4. WCAG 2.2 AA — Critical Success Criteria

### 4.1 Perceivable

| Criterion | ID | SDS Rule |
|-----------|-----|----------|
| Text alternatives | 1.1.1 | All product images have `alt` from merchant; decorative images `alt=""` |
| Color not sole indicator | 1.4.1 | Errors use icon + text; charts use patterns |
| Contrast (minimum) | 1.4.3 | Token pairs audited ≥ 4.5:1 |
| Contrast (enhanced) | 1.4.6 | Target where possible; not required for AA |
| Resize text | 1.4.4 | Layout functional at 200% zoom |
| Reflow | 1.4.10 | No horizontal scroll at 320px width, 400% zoom |
| Non-text contrast | 1.4.11 | UI component borders ≥ 3:1 against adjacent |
| Focus appearance | 2.4.11 | 2px solid ring, 2px offset, ≥ 3:1 contrast (**WCAG 2.2 new**) |
| Target size | 2.5.8 | 44×44px minimum (**WCAG 2.2 new**) |

### 4.2 Operable

| Criterion | ID | SDS Rule |
|-----------|-----|----------|
| Keyboard | 2.1.1 | All functionality via keyboard |
| No keyboard trap | 2.1.2 | Modals: focus trap with Escape to close |
| Focus order | 2.4.3 | DOM order matches visual order |
| Link purpose | 2.4.4 | "View order #1042" not "Click here" |
| Headings & labels | 2.4.6 | Sequential heading hierarchy |
| Focus visible | 2.4.7 | Never `outline: none` without replacement |
| Dragging alternatives | 2.5.7 | Drag reorder has button alternative (**WCAG 2.2 new**) |

### 4.3 Understandable

| Criterion | ID | SDS Rule |
|-----------|-----|----------|
| Language | 3.1.1 | `<html lang="en">` or tenant locale |
| On focus | 3.2.1 | No context change on focus alone |
| Consistent navigation | 3.2.3 | Same nav order across pages |
| Error identification | 3.3.1 | Errors linked to fields via `aria-describedby` |
| Labels/instructions | 3.3.2 | Every input has visible label |
| Error suggestion | 3.3.3 | Error messages include fix guidance |
| Redundant entry | 3.3.7 | Auto-fill saved addresses at checkout (**WCAG 2.2 new**) |

### 4.4 Robust

| Criterion | ID | SDS Rule |
|-----------|-----|----------|
| Parsing | 4.1.1 | Valid HTML (React renders semantic elements) |
| Name, role, value | 4.1.2 | Radix primitives provide ARIA; tested |
| Status messages | 4.1.3 | Toasts use `role="status"` or `role="alert"` |

## 5. Keyboard Navigation

### 5.1 Global Patterns

| Key | Action |
|-----|--------|
| `Tab` / `Shift+Tab` | Move focus forward/backward |
| `Enter` / `Space` | Activate button, toggle checkbox/switch |
| `Escape` | Close modal, sheet, dropdown, popover |
| `Arrow keys` | Navigate within tabs, radio group, select, menu |
| `Home` / `End` | First/last item in listbox, menu |
| `Cmd+K` | Open command palette (admin desktop) |

### 5.2 Focus Management

| Event | Focus Behavior |
|-------|----------------|
| Modal open | Focus first focusable element in modal |
| Modal close | Return focus to trigger element |
| Route change | Focus main content heading (`h1`, `tabindex="-1"`) |
| Toast (error) | Do not steal focus; announce via live region |
| Inline edit | Focus input on edit activation |
| Delete confirm | Focus cancel button (safe default) |

### 5.3 Skip Links

Every page includes visually hidden skip link:

```html
<a href="#main-content" class="sr-only focus:not-sr-only focus:absolute focus:top-4 focus:left-4 focus:z-50 focus:p-2 focus:bg-brand focus:text-white">
  Skip to main content
</a>
```

## 6. Screen Reader Support

### 6.1 Testing Matrix

| Flow | NVDA (Windows) | VoiceOver (iOS) | VoiceOver (macOS) |
|------|----------------|-----------------|-------------------|
| Storefront browse | Required | Required | Required |
| Add to cart | Required | Required | — |
| Checkout (full) | Required | Required | Required |
| Admin product create | Required | — | Required |
| Admin order fulfill | Required | — | — |

Checkout flow (NFR-049) is the launch gate test.

### 6.2 Live Regions

| Event | ARIA | Example |
|-------|------|---------|
| Cart update | `role="status"` | "2 items in cart. Total ₦25,000" |
| Form error | `role="alert"` | "Phone number is invalid" |
| Loading | `aria-busy="true"` | Skeleton container |
| Toast success | `role="status"` | "Product saved" |
| Toast error | `role="alert"` | "Payment failed" |

### 6.3 Landmark Regions

```html
<header role="banner">
<nav role="navigation" aria-label="Main">
<main id="main-content" role="main">
<aside role="complementary" aria-label="Filters">
<footer role="contentinfo">
```

### 6.4 Commerce Announcements

| Action | Screen Reader Announcement |
|--------|---------------------------|
| Add to cart | "Product added to cart. {n} items." |
| Quantity change | "Quantity updated to {n}. Line total ₦{amount}." |
| Remove from cart | "Item removed from cart." |
| Checkout step | "Step {n} of 3: {step name}" |
| Payment redirect | "Redirecting to secure payment. Please wait." |
| Order confirmed | "Order {number} confirmed. Confirmation sent to {phone}." |

## 7. Form Accessibility

### 7.1 Label Association

```tsx
<div>
  <label htmlFor="phone" className="text-sm font-medium">
    Phone number <span aria-hidden="true">*</span>
    <span className="sr-only">(required)</span>
  </label>
  <input
    id="phone"
    type="tel"
    aria-required="true"
    aria-invalid={!!error}
    aria-describedby={error ? "phone-error" : "phone-hint"}
  />
  <p id="phone-hint" className="text-xs text-muted">We'll send order updates via SMS</p>
  {error && <p id="phone-error" role="alert" className="text-sm text-error">{error}</p>}
</div>
```

### 7.2 Error Summary

Forms with ≥ 3 fields show error summary at top on submit failure:

```text
There are 2 errors in this form:
• Phone number — Enter a valid Nigerian mobile number
• Price — Price must be greater than ₦0
```

Summary links focus respective fields on click.

### 7.3 Required Fields

- Visual: asterisk with `(required)` sr-only text
- Programmatic: `aria-required="true"` + `required` attribute
- Optional fields: explicitly marked "(optional)" in label

## 8. Mobile Accessibility

| Requirement | Implementation |
|-------------|----------------|
| Touch targets | 44×44px minimum (NFR-051) |
| Pinch zoom | Never `user-scalable=no` |
| Orientation | Works in portrait and landscape |
| Motion | `prefers-reduced-motion` honored |
| Voice control | Accessible names match visible labels |

## 9. Automated Testing

### 9.1 CI Pipeline

```text
PR → Storybook build → axe-core on all stories → block on critical/serious
PR → Playwright E2E → axe on checkout + admin order flows
Weekly → Full manual audit rotation (storefront, admin, checkout)
```

### 9.2 Tools

| Tool | Use |
|------|-----|
| `@axe-core/react` | Development overlay |
| `@axe-core/playwright` | E2E CI |
| `eslint-plugin-jsx-a11y` | Lint rules in CI |
| Lighthouse accessibility | Score ≥ 95 on reference pages |
| NVDA + VoiceOver | Manual checkout gate |

### 9.3 Acceptance Thresholds

| Severity | CI Action |
|----------|-----------|
| Critical | Block merge |
| Serious | Block merge |
| Moderate | Warning; fix within sprint |
| Minor | Backlog |

## 10. Theme & Merchant Content

Platform controls component accessibility. Merchants control content:

| Merchant Control | Platform Enforcement |
|------------------|---------------------|
| Product image alt text | Required field; warn if empty |
| Custom HTML (CMS) | Sanitized; no `<script>`, no `on*` attributes |
| Theme color overrides | Contrast validation before publish |
| Custom fonts | Legibility check (≥ 14px effective) |
| Video embeds | Require captions (Phase 2 enforcement) |

## 11. Acceptance Criteria

- [ ] axe-core zero critical/serious on all Storybook stories
- [ ] Checkout E2E passes with NVDA (Windows) and VoiceOver (iOS)
- [ ] All admin workflows completable via keyboard only (NFR-048)
- [ ] Lighthouse accessibility ≥ 95 on product page and checkout
- [ ] Focus appearance meets WCAG 2.2 SC 2.4.11 on all interactive elements
- [ ] Touch targets verified at 44px on mobile component variants
- [ ] `prefers-reduced-motion` tested on modal, toast, skeleton, page transition

## 12. Sources

| Source | Confidence |
|--------|------------|
| [WCAG 2.2 (W3C REC)](https://www.w3.org/TR/WCAG22/) | E1 |
| [NFRs](../01-vision/09-non-functional-requirements.md) | E1 |
| [Volume 11](../11-security/README.md) | E1 |
| Radix UI accessibility docs | E1 |
| eslint-plugin-jsx-a11y rules | E1 |
