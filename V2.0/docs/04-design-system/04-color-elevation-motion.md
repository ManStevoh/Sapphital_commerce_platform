# Chapter 04: Color, Elevation & Motion

**Document ID:** SCP-DS-001-04  
**Version:** 1.0.0  
**Status:** ✅ Active  
**Traceability:** NFR-050, NFR-052, Product Principle 1  

---

## 1. Purpose

Expand semantic color usage, elevation (shadow/layer) system, and motion guidelines beyond raw tokens (Chapter 02). This chapter governs **how** color, depth, and animation express state, hierarchy, and feedback across surfaces.

## 2. Color Application Model

### 2.1 The 60-30-10 Rule (Adapted)

| Proportion | Role | Tokens |
|------------|------|--------|
| 60% | Neutral surfaces | `--color-bg`, `--color-bg-subtle`, `--color-surface` |
| 30% | Content & borders | `--color-text`, `--color-text-secondary`, `--color-border` |
| 10% | Brand & accent | `--color-brand`, `--color-action-primary` |

Checkout surfaces shift to **80% neutral** — brand accent limited to CTA button and logo.

### 2.2 State Colors

Interactive elements use consistent state mapping:

| State | Background | Border | Text |
|-------|------------|--------|------|
| Default | `--color-surface` | `--color-border` | `--color-text` |
| Hover | `--color-bg-subtle` | `--color-border-strong` | `--color-text` |
| Focus | `--color-surface` | `--color-brand` (2px ring) | `--color-text` |
| Active/Pressed | `--color-bg-muted` | `--color-brand` | `--color-text` |
| Disabled | `--color-bg-muted` | `--color-border` | `--color-text-muted` (50% opacity) |
| Selected | `--color-brand-subtle` | `--color-brand` | `--color-brand` |
| Error | `--color-error-subtle` | `--color-error` | `--color-error` |

Focus ring specification:

```css
.focus-ring {
  outline: 2px solid var(--color-brand);
  outline-offset: 2px;
}
```

All interactive SDS components include visible focus — never `outline: none` without replacement.

### 2.3 Dark Mode

Dark mode is **opt-in per user** (system preference default) on admin; **merchant-configurable** on storefront themes.

| Principle | Rule |
|-----------|------|
| Not inverted light | Redesigned semantic tokens (Chapter 02) |
| Elevation | Higher layers use lighter surfaces, not stronger shadows |
| Brand | `--color-brand` shifts lighter for contrast on dark bg |
| Images | No automatic inversion; product photos unchanged |
| Checkout | Follows storefront theme; maintains contrast audit |

Toggle: Sun/Moon icon in admin header; stored in `localStorage` + user preference API.

### 2.4 Data Visualization Palette

Analytics charts use a distinct categorical palette — never reuse feedback colors:

| Index | Light | Use |
|-------|-------|-----|
| 1 | `#006644` | Primary series (revenue) |
| 2 | `#2563eb` | Secondary (orders) |
| 3 | `#7c3aed` | Tertiary |
| 4 | `#d97706` | Quaternary |
| 5 | `#64748b` | Comparison / previous period |

Colorblind-safe: pair color with pattern or direct labels on mobile charts.

## 3. Elevation System

Elevation communicates hierarchy through shadow + surface lightness — not arbitrary z-index.

| Level | Token | Shadow | Surface | Use |
|-------|-------|--------|---------|-----|
| 0 | `elevation-0` | none | `--color-bg` | Page background |
| 1 | `elevation-1` | `--shadow-sm` | `--color-surface` | Cards, list items |
| 2 | `elevation-2` | `--shadow-md` | `--color-surface-raised` | Dropdowns, popovers |
| 3 | `elevation-3` | `--shadow-lg` | `--color-surface-raised` | Modals, dialogs |
| 4 | `elevation-4` | `--shadow-xl` | `--color-surface-raised` | Bottom sheets (mobile) |

### 3.1 Layering Rules

- Maximum **one** level-3 overlay at a time (modal OR sheet, not both)
- Dropdowns inside modals: level 2 relative to modal, not new level 3
- Sticky headers: `elevation-1` when scrolled (shadow appears on scroll)
- Checkout: flat (level 0–1 only) — no modals except payment redirect

### 3.2 Border vs Shadow

Prefer borders on admin dense layouts; shadows on storefront cards:

| Surface | Elevation Style |
|---------|-----------------|
| Admin tables | Border only (`--color-border`) |
| Admin cards | Border + `--shadow-sm` |
| Storefront product card | `--shadow-md` on hover |
| Mobile bottom sheet | `--shadow-xl` top edge |

## 4. Motion System

Built on Framer Motion with SDS motion tokens (Chapter 02).

### 4.1 Motion Principles

1. **Purposeful** — motion communicates state change, not decoration
2. **Fast** — admin ≤ 200ms; storefront ≤ 300ms
3. **Respectful** — `prefers-reduced-motion` disables all non-essential animation
4. **Performant** — animate `transform` and `opacity` only; never `width`/`height`

### 4.2 Standard Animations

| Pattern | Duration | Easing | Properties |
|---------|----------|--------|------------|
| Button hover | 100ms | default | background-color |
| Dropdown enter | 200ms | default | opacity, translateY(-4px→0) |
| Modal enter | 200ms | default | opacity, scale(0.95→1) |
| Modal exit | 150ms | default | opacity, scale(1→0.95) |
| Toast enter | 200ms | spring | translateX(100%→0) |
| Toast exit | 150ms | default | opacity |
| Skeleton shimmer | 1500ms | linear | background-position (infinite) |
| Page transition (storefront) | 300ms | default | opacity crossfade |
| Cart badge bounce | 300ms | spring | scale (storefront only) |

### 4.3 Reduced Motion

```tsx
const prefersReducedMotion = useReducedMotion();

<motion.div
  initial={prefersReducedMotion ? false : { opacity: 0, y: -4 }}
  animate={{ opacity: 1, y: 0 }}
  transition={{ duration: prefersReducedMotion ? 0 : 0.2 }}
/>
```

Global CSS fallback:

```css
@media (prefers-reduced-motion: reduce) {
  *, *::before, *::after {
    animation-duration: 0.01ms !important;
    transition-duration: 0.01ms !important;
  }
}
```

### 4.4 Surface-Specific Motion

| Surface | Allowed | Prohibited |
|---------|---------|------------|
| Admin | Subtle fades, skeleton shimmer | Parallax, bounce, auto-play |
| Storefront | Hover lifts, cart bounce, hero fade | Auto-carousel > 5s without pause |
| Checkout | None (instant state changes) | Any entrance animation on pay button |
| Loading | Skeleton shimmer | Full-screen spinners > 1s |

### 4.5 Skeleton Loading

Skeleton screens replace spinners for loads > 300ms:

- Base: `--color-bg-muted`
- Shimmer: linear gradient overlay, 1500ms loop
- Shape matches final content (text lines, image rectangles, button shapes)
- `aria-busy="true"` on container; `aria-label="Loading [content type]"`

## 5. Feedback Patterns

### 5.1 Banner Alerts

| Variant | Background | Icon | Border-left |
|---------|------------|------|-------------|
| Success | `--color-success-subtle` | `CircleCheck` | 4px `--color-success` |
| Warning | `--color-warning-subtle` | `AlertTriangle` | 4px `--color-warning` |
| Error | `--color-error-subtle` | `CircleX` | 4px `--color-error` |
| Info | `--color-info-subtle` | `Info` | 4px `--color-info` |

Dismissible banners: `X` button with `aria-label="Dismiss"`.

### 5.2 Toast Notifications

- Position: top-right (desktop), top-center (mobile)
- Duration: 4s default; 8s for errors; persistent for actionable
- Max visible: 3 stacked
- z-index: `--z-toast`

### 5.3 Inline Validation

- Error text: `--color-error`, `text-sm`, below field
- Error border: `--color-error` on input
- Icon: `CircleX` 16px inline (decorative if text present)
- Success (optional): `--color-success` check after valid blur

## 6. Nigeria UX — Visual Trust

Checkout and payment surfaces apply enhanced trust coloring:

| Element | Treatment |
|---------|-----------|
| "Secure checkout" badge | `--color-success-subtle` bg + lock icon |
| PSP logos row | Grayscale → color on hover (desktop only) |
| SSL indicator | Green lock + "256-bit encryption" caption |
| Order total | Large, `--color-text`, bold, never brand-colored |
| Discount | `--color-success` (savings feel positive) |

## 7. Acceptance Criteria

- [ ] All interactive states documented in Storybook "States" story per component
- [ ] Dark mode contrast audit passes automated check
- [ ] `prefers-reduced-motion` tested on modal, toast, and skeleton
- [ ] Checkout page: zero Framer Motion imports
- [ ] No animation exceeding 300ms on admin surfaces

## 8. Sources

| Source | Confidence |
|--------|------------|
| WCAG 2.2 SC 2.3.3 (Animation from Interactions) | E1 |
| Material Design elevation (adapted) | E3 |
| Framer Motion accessibility docs | E1 |
| Product Principle 1 (Speed) | E1 |
