# Chapter 03: Typography & Iconography

**Document ID:** SCP-DS-001-03  
**Version:** 1.0.0  
**Status:** ✅ Active  
**Traceability:** NFR-050, NFR-057  

---

## 1. Purpose

Specify typefaces, typographic scale, hierarchy, and iconography standards for SDS. Typography must remain legible on low-DPI Android screens at 320px width; icons must supplement labels, not replace them in admin surfaces.

## 2. Typeface Selection

### 2.1 Primary Stack (Platform Default)

| Role | Typeface | Fallback | Rationale |
|------|----------|----------|-----------|
| Body | **Inter** | `system-ui, sans-serif` | Excellent hinting, tabular nums, Latin + extended Latin |
| Headings | **Inter** | same | Single-family consistency (Linear/Stripe pattern) |
| Monospace | **JetBrains Mono** | `ui-monospace, monospace` | Order IDs, API keys, code snippets |

Inter ships via `@fontsource/inter` with subset `latin`, `latin-ext`. Total WOFF2: ~45 KB (weights 400, 500, 600, 700).

### 2.2 Merchant Theme Override

Themes may override via settings (Chapter 10):

- `--font-family-heading`
- `--font-family-body`

**Allowed sources:** Google Fonts catalog (curated allowlist of 24 fonts with proven mobile legibility). Custom font uploads require WOFF2, ≤ 200 KB total, subset to Latin.

**Blocked:** Display fonts below 14px effective size; script fonts; fonts without `font-display: swap`.

### 2.3 Nigeria & Africa Considerations

- Inter supports full Latin Extended — covers English, French (West Africa), Swahili Latin
- Hausa, Yoruba, Igbo use standard Latin alphabet — no special shaping required
- Phase 2: Noto Sans for extended language support (Amharic, Arabic numerals in RTL markets)
- Tabular figures (`font-variant-numeric: tabular-nums`) **required** on price columns and analytics

## 3. Typographic Scale

Base size: **16px** (`--font-size-base`). Never below 14px for body text (NFR-050 large-text threshold at 18px+).

| Token | Size / Line | Weight | Letter-spacing | Use |
|-------|-------------|--------|----------------|-----|
| `text-xs` | 12 / 16 | 400 | 0.01em | Legal footnotes, timestamps |
| `text-sm` | 14 / 20 | 400 | 0 | Secondary labels, table cells |
| `text-base` | 16 / 24 | 400 | 0 | Body, input text |
| `text-lg` | 18 / 28 | 500 | -0.01em | Subheadings, card titles |
| `text-xl` | 20 / 28 | 600 | -0.01em | Product titles (mobile) |
| `text-2xl` | 24 / 32 | 600 | -0.02em | Page titles (admin) |
| `text-3xl` | 30 / 36 | 700 | -0.02em | Storefront hero headings |
| `text-4xl` | 36 / 40 | 700 | -0.03em | Marketing hero (desktop) |

### 3.1 Responsive Type

| Element | Mobile (320–767) | Desktop (1024+) |
|---------|------------------|-----------------|
| Page title | `text-xl` (20px) | `text-2xl` (24px) |
| Product title | `text-lg` (18px) | `text-xl` (20px) |
| Price (primary) | `text-xl` (20px) | `text-2xl` (24px) |
| Body | `text-base` (16px) | `text-base` (16px) |
| Table header | `text-sm` (14px) | `text-sm` (14px) |

Do not scale body below 16px on mobile — readability on low-end screens is paramount.

## 4. Hierarchy Rules

### 4.1 Admin / Dashboard

```text
Page Title (text-2xl, semibold)
  └── Description (text-sm, text-secondary) — one line max
Section Heading (text-lg, medium)
  └── Section description (text-sm, text-muted) — optional
Card Title (text-base, medium)
Body (text-base, regular)
Caption (text-xs, text-muted)
```

Maximum **two heading levels** visible without scroll on mobile admin screens.

### 4.2 Storefront

```text
Hero Heading (text-3xl → text-4xl at lg)
Collection Title (text-2xl)
Product Title (text-lg → text-xl)
Price (text-xl, tabular-nums, font-semibold)
Compare-at Price (text-sm, text-muted, line-through)
Product Description (text-base, prose max-w-prose)
```

### 4.3 Prose (CMS / Product Descriptions)

Use `@tailwindcss/typography` with SDS overrides:

- Max line length: 65 characters (`max-w-prose`)
- Paragraph spacing: `--space-2`
- Link color: `--color-text-link` with underline on hover
- Heading sizes capped at `text-2xl` within product descriptions

## 5. Font Loading Strategy

Performance-critical path (NFR-001):

1. Preload Inter 400 and 600 WOFF2 in `<head>`
2. `font-display: swap` on all weights
3. System font stack renders immediately; swap on load
4. Theme custom fonts load async — fall back to Inter until ready
5. No FOIT (flash of invisible text) permitted

```html
<link rel="preload" href="/fonts/inter-latin-400.woff2" as="font" type="font/woff2" crossorigin />
```

## 6. Iconography — Lucide

**Library:** [Lucide React](https://lucide.dev) — stroke-based, 24×24 default viewBox, 2px stroke.

### 6.1 Sizing

| Token | Size | Stroke | Use |
|-------|------|--------|-----|
| `icon-xs` | 14px | 1.5px | Inline with `text-xs` |
| `icon-sm` | 16px | 2px | Inline with `text-sm`, buttons |
| `icon-md` | 20px | 2px | Default standalone |
| `icon-lg` | 24px | 2px | Navigation, empty states |
| `icon-xl` | 32px | 2px | Feature highlights |

Icons in touch targets inherit button padding to achieve 44×44 minimum (NFR-051).

### 6.2 Icon + Label Rules

| Surface | Rule |
|---------|------|
| Admin navigation | Icon + text label always |
| Admin toolbar | Icon-only allowed with `aria-label` + tooltip |
| Storefront mobile nav | Icon + short label (≤ 8 chars) |
| Storefront product actions | Icon + label ("Add to cart") |
| Checkout | Text-primary; icons supplementary only |

### 6.3 Standard Icon Mapping

| Action | Lucide Icon | Notes |
|--------|-------------|-------|
| Add / Create | `Plus` | Primary create actions |
| Edit | `Pencil` | Inline edit |
| Delete | `Trash2` | Destructive — red on confirm |
| Save | `Check` | Inside save button |
| Search | `Search` | Left-aligned in input |
| Filter | `SlidersHorizontal` | Opens filter sheet |
| Cart | `ShoppingCart` | Badge for item count |
| Orders | `Package` | Admin nav |
| Analytics | `BarChart3` | Dashboard |
| Settings | `Settings` | Gear |
| Payment | `CreditCard` | Checkout |
| Phone | `Phone` | Nigeria identity flows |
| Location | `MapPin` | Shipping, pickup points |
| Warning | `AlertTriangle` | Warning banners |
| Error | `CircleX` | Error states |
| Success | `CircleCheck` | Confirmation |
| Menu | `Menu` | Mobile hamburger |
| Close | `X` | Dismiss modals |
| Chevron | `ChevronRight` | Navigation affordance |
| External link | `ExternalLink` | Opens new tab |
| AI suggest | `Sparkles` | AI features only |

Do not mix icon libraries. Custom SVGs must match Lucide 2px stroke and 24px viewBox.

### 6.4 Commerce Icons (Nigeria)

| Context | Icon | Label Required |
|---------|------|----------------|
| Paystack | Brand SVG (official) | "Pay with Paystack" |
| Bank transfer | `Landmark` | "Bank transfer" |
| Cash on delivery | `Banknote` | "Pay on delivery" |
| USSD | `Smartphone` | "Pay with USSD" |
| M-Pesa (Kenya) | Brand SVG | "M-Pesa" |

PSP brand icons follow official brand guidelines; minimum height 24px.

## 7. Number & Currency Typography

Naira and price display rules:

```tsx
// Correct
<span className="tabular-nums font-semibold text-xl">
  ₦12,500.00
</span>

// Sale pricing
<span className="tabular-nums font-semibold text-error">₦9,999.00</span>
<s className="tabular-nums text-sm text-muted">₦12,500.00</s>
```

| Rule | Specification |
|------|---------------|
| Currency symbol | Prefix: `₦` (no space) |
| Thousands separator | Comma: `₦1,234,567.00` |
| Decimals | Always 2 for NGN |
| Negative | Prefix minus: `-₦500.00` |
| Range | `₦5,000 – ₦8,000` (en dash) |
| Compact (analytics) | `₦1.2M`, `₦450K` with tooltip for exact |

Use `@scp/i18n` `formatCurrency()` — never concatenate strings in components.

## 8. Accessibility

- Minimum text size: 14px (`text-sm`); 12px only for non-essential metadata
- Contrast: all text tokens meet NFR-050 (Chapter 02)
- Icons without visible text: `aria-hidden="false"` + `aria-label`
- Decorative icons: `aria-hidden="true"`
- Heading order: sequential (`h1` → `h2` → `h3`); one `h1` per page

## 9. Acceptance Criteria

- [ ] Inter 400/600 preloaded; LCP font contribution ≤ 50ms
- [ ] All admin nav items have icon + visible label
- [ ] Price displays use `tabular-nums` across storefront and admin
- [ ] Lucide tree-shaking verified — no full icon bundle in production
- [ ] Theme font override validated for 14px minimum legibility

## 10. Sources

| Source | Confidence |
|--------|------------|
| Inter font specimen | E1 |
| Lucide icon guidelines | E1 |
| WCAG 2.2 SC 1.4.3, 1.4.4 | E1 |
| Shopify Polaris typography | E3 |
