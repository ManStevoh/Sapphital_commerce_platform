# Chapter 06: Core Component Inventory

**Document ID:** SCP-DS-001-06  
**Version:** 1.0.0  
**Status:** ✅ Active  
**Traceability:** NFR-051, NFR-053, Product Principles 2, 3, 5  

---

## 1. Purpose

Provide detailed specifications for every SDS business and key base component. This inventory is the build checklist for `@scp/ui` and `@scp/commerce-ui`.

## 2. Inputs

### 2.1 Text Input (`Input`)

| Property | Specification |
|----------|---------------|
| Height | 40px (md), 36px (sm), 44px (lg — mobile default) |
| Padding | `--space-2` horizontal |
| Border | 1px `--color-border`; 2px `--color-brand` on focus |
| Label | Above field, `text-sm`, `--color-text` |
| Hint | Below field, `text-xs`, `--color-text-muted` |
| Error | Below field, `text-sm`, `--color-error`; border `--color-error` |
| Required | Asterisk + `aria-required="true"` |

### 2.2 Price Input (`PriceInput`)

Nigeria-first currency input for merchant admin.

| Property | Specification |
|----------|---------------|
| Format | Displays `₦` prefix; stores integer minor units (kobo) |
| Input type | `tel` (mobile numeric keyboard) |
| Placeholder | `0.00` |
| Validation | ≥ 0; max 999,999,999.99 NGN |
| Locale | Reads tenant currency from context |

```tsx
<PriceInput
  label="Price"
  value={1250000}           // 12500.00 NGN in kobo
  currency="NGN"
  onChange={(kobo) => setPrice(kobo)}
  error={errors.price}
/>
```

### 2.3 Phone Input (`PhoneInput`)

| Property | Specification |
|----------|---------------|
| Default country | NG (+234) |
| Format display | `0803 456 7890` (local) while storing E.164 |
| Validation | Nigerian mobile prefixes: 070, 080, 081, 090, 091 |
| Input type | `tel` |
| Icon | `Phone` prefix |
| Autocomplete | `tel-national` |

Kenya expansion: default country `KE`, M-Pesa number validation.

### 2.4 Address Form (`AddressForm`)

| Field | Required (NG) | Notes |
|-------|-----------------|-------|
| Full name | Yes | |
| Phone | Yes | `PhoneInput` |
| Street address | Yes | |
| City | Yes | |
| State | Yes | Nigerian states dropdown (37 entries) |
| LGA | Optional Phase 1 | Local Government Area |
| Postal code | No | Not used in Nigeria; hidden for NG locale |
| Country | Yes | Default NG |

### 2.5 Select / Combobox

| Property | Specification |
|----------|---------------|
| Mobile | Full-width bottom sheet with search |
| Desktop | Popover dropdown with typeahead |
| Max visible | 8 items before scroll |
| Empty | "No results found" + clear search |

### 2.6 Variant Picker (`VariantPicker`)

| Property | Specification |
|----------|---------------|
| Display | Pill buttons (size), swatches (color), dropdown (material) |
| Unavailable | Strikethrough + `aria-disabled` |
| Selected | `--color-brand-subtle` bg, `--color-brand` border |
| Touch target | 44px minimum per option |

### 2.7 Media Uploader

| Property | Specification |
|----------|---------------|
| Accept | image/jpeg, image/png, image/webp |
| Max size | 5 MB per file (admin); compressed to WebP on upload |
| Preview | Thumbnail grid with drag reorder |
| Mobile | Camera capture option (`capture="environment"`) |
| Progress | Per-file progress bar |
| 3G fallback | Show placeholder thumbnail; load full on WiFi tap |

## 3. Feedback

### 3.1 Order Status Badge (`OrderStatusBadge`)

| Status | Color | Icon |
|--------|-------|------|
| Pending | warning | `Clock` |
| Paid | info | `CreditCard` |
| Processing | info | `Package` |
| Shipped | info | `Truck` |
| Delivered | success | `CircleCheck` |
| Cancelled | error | `CircleX` |
| Refunded | warning | `RotateCcw` |

### 3.2 Stock Badge (`StockBadge`)

| Condition | Label | Color |
|-----------|-------|-------|
| In stock | "In stock" | success |
| Low (≤ threshold) | "Only {n} left" | warning |
| Out of stock | "Sold out" | muted |

### 3.3 Payment Status Banner (`PaymentStatusBanner`)

Used on checkout return and order detail:

| State | Message (NG) |
|-------|--------------|
| Redirecting | "Redirecting to Paystack…" + spinner |
| Pending | "Payment processing. We'll SMS you when confirmed." |
| Success | "Payment successful! Order #SCP-{id} confirmed." |
| Failed | "Payment failed. Try again or choose another method." |

### 3.4 Empty State (`EmptyState`)

| Property | Specification |
|----------|---------------|
| Illustration | 120px Lucide composition or SVG |
| Headline | `text-lg`, semibold |
| Description | `text-sm`, `--color-text-secondary`, max 2 lines |
| Action | Single primary button |
| Example | "No products yet" → "Add your first product" |

## 4. Navigation

### 4.1 Admin Sidebar (`AdminSidebar`)

Desktop (≥1024px):

| Property | Specification |
|----------|---------------|
| Width | 256px expanded, 64px collapsed |
| Items | Icon + label; max 7 top-level |
| Active | `--color-brand-subtle` bg, `--color-brand` text |
| Sections | Collapsible groups with chevron |
| Footer | Merchant name + plan badge |

### 4.2 Mobile Tab Bar (`MobileTabBar`)

Mobile admin (<1024px):

| Property | Specification |
|----------|---------------|
| Position | Fixed bottom |
| Height | 56px + safe-area-inset |
| Items | 5 max: Home, Products, Orders, Analytics, More |
| Active | `--color-brand` icon + label |
| Badge | Order count on Orders tab |

### 4.3 Storefront Header (`StorefrontHeader`)

| Property | Specification |
|----------|---------------|
| Mobile | Logo center, hamburger left, cart right |
| Desktop | Logo left, nav center, search + cart right |
| Sticky | `--shadow-sm` on scroll |
| Cart badge | Item count, `--color-brand` bg |

### 4.4 Breadcrumb

| Property | Specification |
|----------|---------------|
| Separator | `ChevronRight` 14px |
| Max items | 4 visible; truncate middle with `…` |
| Mobile | Hidden on storefront; back arrow instead |

## 5. Commerce

### 5.1 Product Card (`ProductCard`)

| Property | Mobile | Desktop |
|----------|--------|---------|
| Image ratio | Theme contract; default 1:1 | Same; editorial themes may use 4:5 |
| Title lines | 2 (truncate visually) | 2 |
| Rating | Value + count when reviews exist | Same |
| Price | Always visible; `text-lg`, tabular | `text-xl`, tabular |
| Badges | Maximum 2 factual badges | Same |
| Wishlist | Persistent touch button | Visible default or on hover/focus |
| Quick view | Bottom sheet if enabled | Accessible dialog on hover/focus action |
| Quick add | Optional persistent button | Reveal without layout shift |
| Skeleton | Exact image, text, rating, price, and CTA geometry | Same |

**Interaction contract:**

- Hover/focus image scale ≤ 1.03 and card elevation increases one level.
- Hover-only controls also appear on keyboard focus and have touch equivalents.
- Quick Add opens variant selection when a product has ambiguous variants.
- Sold-out cards replace Add to Cart with “View product” or “Notify me”.
- Alternate-image crossfade loads after idle and never affects LCP.
- All card actions have accessible names and visible focus.

Nigeria: price always displays in NGN (`₦`) for NG stores; no “from” prefix unless variants differ in price. Kenya stores display KES (`KSh`) from tenant locale.

Full variants—Standard, Editorial, Compact, List, Marketplace, Course, and Food—are normative in [Chapter 13](./13-storefront-visual-direction.md).

### 5.1.1 Product Quick View (`ProductQuickView`)

| Property | Specification |
|----------|---------------|
| Content | Image, title, price, rating, variants, stock, Add to Cart, PDP link |
| Desktop | Modal dialog; focus trapped; Escape closes |
| Mobile | Bottom sheet, maximum 90dvh, safe-area padding |
| Data | Current Commerce API price/stock; no stale card payload |
| URL | Opening updates history state; closing restores focus and URL |
| Performance | Lazy-loaded; not part of initial product-grid JS |

### 5.2 Cart Line Item (`CartLineItem`)

| Property | Specification |
|----------|---------------|
| Layout | Image (64px) + details + quantity stepper + line total |
| Quantity | `-` / count / `+` buttons, 44px touch targets |
| Remove | `Trash2` icon with "Remove" label (mobile: swipe-to-delete) |
| Optimistic | Quantity change updates total immediately |

### 5.3 Checkout Summary (`CheckoutSummary`)

| Property | Specification |
|----------|---------------|
| Position | Sticky bottom bar (mobile), sidebar (desktop) |
| Contents | Subtotal, shipping, tax, discount, **Total** |
| Total | `text-xl`, bold, tabular-nums |
| Collapse | Mobile: tap to expand line items |

### 5.4 Payment Method Selector (`PaymentMethodSelector`)

Nigeria default ordering:

| Priority | Method | Icon |
|----------|--------|------|
| 1 | Paystack (card + bank) | Paystack logo |
| 2 | Flutterwave | Flutterwave logo |
| 3 | Bank transfer | `Landmark` |
| 4 | Cash on delivery | `Banknote` |
| 5 | USSD | `Smartphone` |

| Property | Specification |
|----------|---------------|
| Selection | Radio group, full-width cards |
| Selected | `--color-brand` border, check icon |
| Disabled | Method unavailable for order total |
| Trust | PSP logos + "Secured by {psp}" caption |

### 5.5 Product Form (`ProductForm`)

Merchant product create/edit — progressive disclosure:

| Section | Default State | Fields |
|---------|---------------|--------|
| Basic | Open | Title, description, price, photos |
| Inventory | Collapsed | SKU, quantity, track inventory |
| Variants | Collapsed | Options, variant matrix |
| Shipping | Collapsed | Weight, dimensions, HS code |
| SEO | Collapsed | Meta title, description, URL handle |
| Advanced | Collapsed | Tags, vendor, custom fields |

Primary action: sticky "Save product" footer on mobile.

## 6. Analytics

### 6.1 Metric Card (`MetricCard`)

| Property | Specification |
|----------|---------------|
| Layout | Label (sm) + value (2xl, tabular) + delta badge |
| Delta | Green up / red down + percentage |
| Period | "vs previous 30 days" caption |
| Loading | Skeleton: label line + value rect |
| Mobile | Full width, stack 1-column |

Example metrics: Total revenue (₦), Orders, Conversion rate, AOV.

### 6.2 Revenue Chart (`RevenueChart`)

| Property | Specification |
|----------|---------------|
| Type | Area chart (default), bar toggle |
| Period | 7d / 30d / 90d / 12m pills |
| Y-axis | Compact NGN (`₦1.2M`) |
| Tooltip | Exact amount on tap/hover |
| Empty | Flat line at 0 + "No sales yet" |
| Mobile | Full width, 200px height, swipe between periods |

### 6.3 Orders Sparkline (`OrdersSparkline`)

Inline 7-day order trend for dashboard table rows. 80×24px, no axes, `--color-brand` fill.

### 6.4 Data Table (`DataTable`)

Admin workhorse for products, orders, customers.

| Property | Specification |
|----------|---------------|
| Mobile | Card list view (not horizontal scroll) |
| Desktop | Sortable columns, row selection checkbox |
| Pagination | 25/50/100 per page |
| Bulk actions | Sticky bar on selection |
| Empty | `EmptyState` component |
| Loading | 5 skeleton rows |

## 7. Component Status Matrix

| Component | Layer | Phase 1 | Storybook | Tests |
|-----------|-------|---------|-----------|-------|
| Button | Base | ✅ | Required | Required |
| Input | Base | ✅ | Required | Required |
| PriceInput | Business | ✅ | Required | Required |
| PhoneInput | Business | ✅ | Required | Required |
| ProductCard | Business | ✅ | Required | Required |
| CartLineItem | Business | ✅ | Required | Required |
| CheckoutSummary | Business | ✅ | Required | Required |
| PaymentMethodSelector | Business | ✅ | Required | Required |
| AdminSidebar | Business | ✅ | Required | E2E |
| MobileTabBar | Business | ✅ | Required | E2E |
| MetricCard | Business | ✅ | Required | Required |
| RevenueChart | Business | ✅ | Required | Required |
| DataTable | Business | ✅ | Required | E2E |
| AddressForm | Business | ✅ | Required | Required |
| VariantPicker | Business | Phase 1.1 | Required | Required |
| MediaUploader | Business | ✅ | Required | Required |
| ProductQuickView | Business | Phase 1.1 | Required | E2E |
| StorefrontMegaMenu | Business | Phase 1.1 | Required | E2E |
| StorefrontMobileNav | Business | Phase 1.1 | Required | E2E |
| AIProductFinder | Business | Phase 2 | Required | E2E |

## 8. Acceptance Criteria

- [ ] 100% of Phase 1 components in Storybook with all variants
- [ ] PriceInput and PhoneInput pass Nigeria locale test fixtures
- [ ] PaymentMethodSelector renders Paystack/Flutterwave by default for NG tenants
- [ ] DataTable card view on mobile — no horizontal scroll at 320px
- [ ] All touch targets ≥ 44px verified on mobile variants
- [ ] Product Card hover, focus, touch, sale, sold-out, and loading states documented
- [ ] Quick View restores focus and never ships in the initial storefront bundle
- [ ] Fixed storefront navigation, AI, consent, and purchase controls pass collision tests

## 9. Sources

| Source | Confidence |
|--------|------------|
| Shopify admin component patterns | E3 |
| Stripe Dashboard metric cards | E3 |
| Product Principles 2, 3, 5 | E1 |
| CBN payment method categories | E2 |
