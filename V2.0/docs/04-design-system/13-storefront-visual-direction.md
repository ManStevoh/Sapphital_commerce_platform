# Chapter 13: Storefront Visual Direction & Conversion Patterns

**Document ID:** SCP-DS-001-13  
**Version:** 1.0.0  
**Status:** ✅ Active  
**Traceability:** ADR-003, ADR-012, PRD-001, PRD-006, FR-AI-003, NFR-001, NFR-047–NFR-053  

---

## Purpose

Define what an SCP storefront **must look and feel like**. This chapter turns the platform architecture into a normative visual and conversion specification so a storefront feels commissioned from a premium design agency while remaining schema-driven, fast, accessible, and easy for a merchant to operate.

This chapter is the visual north star for:

- Built-in themes and Theme Marketplace review
- Storefront components in `@scp/commerce-ui`
- Homepage, collection, product, search, cart, and AI-assistant surfaces
- Figma reference screens and visual-regression fixtures
- Merchant presets and vertical starter themes

## Design Position

> **Custom-built character above template sameness; commerce clarity above decoration.**

SCP does not reproduce a recognizable Shopify-theme aesthetic. The storefront is specified as a **living digital salesperson** across eight experience layers (Volume 6 Ch. 12), not a static product grid.

The platform standardizes **contracts and quality**, not a single visual skin.

---

## 1. The Five-Second Storefront Test

Within five seconds of the first meaningful paint, a first-time visitor must be able to answer:

1. **What does this store sell?**
2. **Why should I trust this store?**
3. **What should I do next to buy?**

### 1.1 Required Above-the-Fold Evidence

| Question | Required evidence | Typical component |
|----------|-------------------|-------------------|
| What is sold? | Specific category/value proposition, not a generic slogan | Hero heading + relevant product imagery |
| Why trust it? | Delivery promise, verification, review count, returns, secure payment, or physical location | Announcement/trust bar or hero proof point |
| How to buy? | One visually dominant commerce CTA | “Shop phones”, “Order meals”, “Browse courses” |

### 1.2 Acceptance Test

Conduct an unmoderated five-second test with at least five target-market participants per reference theme:

- ≥ 80% correctly identify the product/service category.
- ≥ 80% identify the primary CTA.
- ≥ 60% recall at least one genuine trust signal.
- No participant interprets decorative copy as the primary action.

A theme failing this test cannot be designated **SCP Recommended**.

---

## 2. Visual Character

### 2.1 Agency-Quality Characteristics

| Characteristic | Required expression |
|----------------|---------------------|
| Editorial hierarchy | Intentional type scale, strong headline, quiet supporting copy |
| Generous composition | Visible whitespace; sections do not appear as stacked admin cards |
| Art direction | Crops and media ratios selected for the vertical and breakpoint |
| Controlled color | Neutral canvas with brand/accent color used deliberately |
| Distinct rhythm | Alternating dense merchandising and spacious storytelling sections |
| Material quality | Crisp image treatment, subtle borders/elevation, no default browser styling |
| Conversion clarity | One primary CTA per decision region |
| Restraint | Motion and badges communicate; they do not compete for attention |

### 2.2 Anti-Patterns

Theme review rejects:

- A generic full-width slideshow followed by identical card grids with no hierarchy
- More than two competing primary buttons above the fold
- Tiny product cards with unreadable price, rating, or CTA
- Excessive rounded cards around every section
- Fake countdowns, fake low-stock claims, or fabricated social proof
- Autoplay video with sound or autoplay carousels without pause controls
- Large decorative animation delaying product discovery
- Desktop composition merely scaled down to mobile
- Hard-coded Nigeria copy in themes listed as pan-African
- Price shown as KSh on Nigeria stores; Nigeria defaults to **NGN (`₦`)**, Kenya to **KES (`KSh`)**

---

## 3. Desktop Homepage Reference Composition

```text
┌──────────────────────────────────────────────────────────────────────┐
│ ANNOUNCEMENT: Same-day Lagos delivery • Secure Paystack checkout    │
├──────────────────────────────────────────────────────────────────────┤
│ LOGO          Search products…            Wishlist  Account  Cart   │
├──────────────────────────────────────────────────────────────────────┤
│ New in  Shop  Collections  Brands  About        Featured deal       │
├──────────────────────────────────────────────────────────────────────┤
│                                                                      │
│  Premium electronics for modern living          Art-directed media  │
│  Genuine devices. Local warranty. Fast delivery. image/video poster  │
│  [Shop electronics]  [Watch the story]                              │
│  ★ 4.8 from verified buyers • Paystack • 7-day returns              │
│                                                                      │
├──────────────────────────────────────────────────────────────────────┤
│  Featured categories — large editorial tiles                        │
├──────────────────────────────────────────────────────────────────────┤
│  Best sellers — product grid with clear price and quick actions      │
├──────────────────────────────────────────────────────────────────────┤
│  Campaign / flash-sale banner — factual deadline and offer           │
├──────────────────────────────────────────────────────────────────────┤
│  “Tell us what you need” — embedded AI discovery prompt              │
├──────────────────────────────────────────────────────────────────────┤
│  New arrivals / personalized recommendations                         │
├──────────────────────────────────────────────────────────────────────┤
│  Lifestyle story / video poster / brand proof                        │
├──────────────────────────────────────────────────────────────────────┤
│  Reviews • brands • social proof • editorial • FAQ • newsletter      │
├──────────────────────────────────────────────────────────────────────┤
│  Footer: shop, help, policies, contact, payments, locale              │
└──────────────────────────────────────────────────────────────────────┘
```

This is a reference rhythm, not a fixed template. A merchant may reorder or remove optional sections.

### 3.1 Recommended Homepage Sequence

| Order | Section | Objective | Requirement |
|-------|---------|-----------|-------------|
| 1 | Announcement / trust bar | Immediate proof | One concise message; dismissible |
| 2 | Header + navigation | Find and orient | Search and cart always reachable |
| 3 | Hero | Explain and direct | Five-second test; one primary CTA |
| 4 | Featured categories | Route intent | 3–8 visual category tiles |
| 5 | Best sellers | Reduce choice anxiety | 4–12 products; verified merchandising |
| 6 | Campaign banner | Promote active offer | Real dates and promotion entity |
| 7 | AI product finder | Natural-language discovery | Embedded prompt, not only floating chat |
| 8 | New/personalized products | Increase relevance | Honest reason label |
| 9 | Lifestyle/brand story | Build desire and identity | Media + short narrative |
| 10 | Testimonials/reviews | Build trust | Verified source labels |
| 11 | Brands/social/editorial | Extend discovery | Optional by vertical |
| 12 | FAQ | Remove objections | Shipping, returns, payment |
| 13 | Newsletter/WhatsApp opt-in | Retention | Separate, explicit consent |
| 14 | Footer | Support and legal | Complete policy/contact routes |

No homepage must contain every section. Reference themes ship with **8–12 sections** and merchants may add up to the platform limit.

---

## 4. Mobile Storefront Reference

```text
┌─────────────────────────────┐
│ Delivery promise / offer    │
├─────────────────────────────┤
│ ☰      LOGO       Search 🛒 │
├─────────────────────────────┤
│ Clear category headline     │
│ Supporting proof            │
│ [Primary shopping action]   │
│ Art-directed mobile image   │
├─────────────────────────────┤
│ Swipeable categories        │
├─────────────────────────────┤
│ Two-column product grid     │
├─────────────────────────────┤
│ Embedded AI product finder  │
├─────────────────────────────┤
│ Remaining sections          │
├─────────────────────────────┤
│ Home Categories Search ♥ Me │
└─────────────────────────────┘
```

### 4.1 Bottom Navigation

For stores enabling persistent mobile navigation:

| Position | Item | Behavior |
|----------|------|----------|
| 1 | Home | Storefront root |
| 2 | Categories | Full category/collection sheet |
| 3 | Search | Opens focused search with keyboard |
| 4 | Wishlist | Persistent when signed in; session fallback |
| 5 | Account | Orders, profile, login |

The cart remains visible in the sticky header with an item-count badge. The bottom bar must not obscure cookie controls, chat, or sticky purchase actions. On PDP, the sticky add-to-cart bar takes precedence; mobile navigation may collapse while the purchase bar is visible.

### 4.2 Mobile Rules

- Minimum touch target: 44×44 px; primary commerce controls: 48 px high.
- No horizontal page scrolling at 320 px.
- Hero has a dedicated mobile crop and may place media below copy.
- Product grids use two columns by default; one column when card content requires it.
- Search, cart, and checkout entry remain reachable in one tap.
- Safe-area insets applied to bottom navigation and sticky bars.

---

## 5. Header, Search & Mega Navigation

### 5.1 Header Layers

1. Optional announcement/trust bar
2. Primary utility row: logo, search, wishlist, account, cart
3. Desktop category navigation

The header becomes compact and sticky after scroll; it must not consume more than 96 px on a 667 px mobile viewport after compaction.

### 5.2 Mega Menu Contract

Desktop stores with more than seven top-level destinations use a mega menu:

```text
Electronics
├── Phones
├── Laptops
├── Gaming
├── Accessories
├── Smart Home
└── Featured: Deals • Best Sellers • New Arrivals
```

| Requirement | Specification |
|-------------|---------------|
| Open behavior | Click and keyboard activation; hover may preview after 150 ms |
| Columns | 2–5 groups; no more than 10 links per group |
| Promotional tile | Maximum 1; must not displace navigation |
| Keyboard | Arrow-key navigation, Escape closes, focus returns to trigger |
| Mobile | Full-height drill-down sheet with back navigation |
| Performance | Menu data rendered in initial HTML; promo image lazy-loaded |

### 5.3 Search

- Typeahead begins after two characters and 200 ms debounce.
- Results group products, categories, and suggested queries.
- Product suggestions include thumbnail, title, and current localized price.
- “No results” offers spelling recovery, categories, and optional AI product finder.

---

## 6. Product Card Contract

Product cards are the most repeated conversion component and must remain readable at the smallest supported grid width.

```text
┌─────────────────────────────┐
│ Badge                ♡      │
│                             │
│        Product image        │
│                             │
│                Quick view   │
├─────────────────────────────┤
│ Wireless headphones         │
│ ★ 4.8 (245)                 │
│ ₦89,999  ₦99,999            │
│ [Add to cart]               │
└─────────────────────────────┘
```

### 6.1 Required Anatomy

| Element | Rule |
|---------|------|
| Image | Consistent ratio chosen by theme; no distortion |
| Badge | Maximum two; sale, new, low-stock, or verified factual status |
| Wishlist | Icon button with accessible name and visible selected state |
| Title | Two-line maximum; full title available to assistive technology |
| Rating | Show only when reviews exist; include count |
| Price | Always visible; localized; sale and compare-at hierarchy |
| Variant cue | “3 colours” or swatches; optional |
| Commerce action | Desktop quick-add; mobile configurable quick-add or PDP link |

### 6.2 Interaction

- Hover/focus: image scale ≤ 1.03, elevation increases one level, actions appear without layout shift.
- Alternate image may crossfade if preloaded after idle.
- Quick View opens an accessible dialog on desktop and bottom sheet on mobile.
- Quick Add must require variant choice where variants are ambiguous.
- All hover-only controls must also appear on keyboard focus and have a touch equivalent.
- Loading skeleton preserves image ratio and text/button dimensions.

### 6.3 Card Variants

| Variant | Use |
|---------|-----|
| Standard | PLP and homepage grids |
| Editorial | Large media, minimal metadata for curated collections |
| Compact | Search suggestions and recommendations |
| List | Search/accessibility preference and service products |
| Marketplace | Adds vendor identity and verification |
| Course | Adds duration, level, and instructor |
| Food | Adds prep time, dietary badges, and availability |

---

## 7. Product Detail Page (PDP)

### 7.1 Desktop Composition

```text
┌──────────────────────────────┬───────────────────────────────┐
│ Media gallery                │ Purchase panel                │
│ thumbnails + main image      │ Product name                  │
│ zoom / video / 360 (optional)│ Rating + review link          │
│                              │ Price / financing             │
│                              │ Variants                      │
│                              │ Stock + delivery estimate     │
│                              │ [Add to cart] [Buy now]       │
│                              │ Wishlist • returns • trust    │
└──────────────────────────────┴───────────────────────────────┘
```

The purchase panel is sticky only while its content fits the viewport. It must never hide validation errors or shipping information.

### 7.2 Mobile Composition

1. Swipeable media gallery with position indicator
2. Title, rating, price, promotion
3. Variant selection
4. Stock and location-aware delivery estimate
5. Sticky Add to Cart / Buy Now region
6. Trust and returns summary
7. Expandable detail sections

### 7.3 Below-Purchase Sequence

| Section | Requirement |
|---------|-------------|
| Description | Benefit-led summary; full structured content |
| Specifications | Scannable definition table |
| AI summary | Clearly labelled; grounded in product facts; optional |
| Reviews | Verified purchase state and rating distribution |
| Questions | Merchant/customer Q&A with moderation |
| Frequently bought together | Explain compatibility or bundle value |
| Related products | Explain recommendation basis |
| Recently viewed | Local/session or consented account history |

### 7.4 Media

- First image is the LCP candidate and preloaded.
- Gallery supports image, muted video poster, and optional 360° media.
- Autoplay is off; video requires explicit play.
- Zoom has keyboard controls and does not trap focus.
- Product media includes meaningful alt text; decorative lifestyle media may use empty alt.

---

## 8. Embedded AI Shopping Assistant

The assistant is a **shopping surface**, not merely a generic chatbot bubble.

### 8.1 Homepage Product-Finder Section

```text
Need help finding the right product?
[ Ask for a product, budget, occasion, or use case… ]

Try:
• Phone under ₦250,000
• Birthday gift for my wife
• School shoes, size 36
• Coffee machine for a small office
```

### 8.2 Placement Modes

| Mode | Use |
|------|-----|
| Embedded product finder | Homepage, collection no-results, category landing |
| Floating launcher | Persistent help; lazy-loaded and non-intrusive |
| Search escalation | Offered after weak/no search results |
| PDP questions | Grounded questions about the current product |

### 8.3 Guardrails

- AI never obscures cart, checkout, mobile navigation, or sticky Add to Cart.
- Suggestions show native Product Card Compact components.
- Price and stock are fetched live from Commerce APIs.
- Add to Cart requires a visible confirmation action.
- Personal memory requires consent and provides “Why am I seeing this?”.
- The assistant may match English or Nigerian Pidgin; legal/product facts remain grounded.

---

## 9. Personalization

Personalization improves relevance without making the storefront feel surveillant.

### 9.1 Eligible Modules

- Continue shopping
- Recently viewed
- Recommended for you
- Accessories compatible with a prior purchase
- Reorder common consumables
- Loyalty balance and rewards
- Location-relevant delivery or collection availability

### 9.2 Rules

| Rule | Requirement |
|------|-------------|
| Cold start | Show best sellers / editorial picks, not empty personalized areas |
| Explanation | Label reason: “Based on items you viewed” |
| Consent | Cross-session behavioral personalization follows consent policy |
| Control | Customer may clear recently viewed and disable personalization |
| Sensitive inference | Prohibited |
| Layout stability | Personalized module cannot move primary CTA below the fold |

---

## 10. Vertical Theme Families

SCP launches curated **theme families**, not one generic theme recolored repeatedly.

| Family | Starter verticals | Visual emphasis | Specialized sections |
|--------|-------------------|-----------------|----------------------|
| Retail | Fashion, electronics, supermarket, furniture, books, beauty, jewelry | Product imagery and merchandising | Size guide, brands, comparison, flash sale |
| Food | Restaurant, coffee, bakery, grocery, meal delivery | Appetite imagery and immediacy | Menu, dietary filters, hours, delivery radius |
| Services | Agency, consultant, freelancer, repair, booking | Credibility and outcomes | Services, case studies, team, booking CTA |
| Education | Courses, schools, bootcamps, universities | Trust, progression, instructor authority | Curriculum, outcomes, instructor, cohorts |
| Digital | Software, downloads, templates, subscriptions | Product demonstration and proof | Feature comparison, demo, license options |
| Marketplace | Multi-vendor retail and local markets | Discovery and seller trust | Vendor spotlight, verified seller, commissions |

### 10.1 Required Differentiation

Each family must differ in more than color:

- Composition and grid behavior
- Type pairing and scale
- Media ratios and art direction
- Header/navigation treatment
- Product-card variant
- Default section rhythm
- Specialized section inventory

---

## 11. Motion & Microinteractions

| Interaction | Motion | Limit |
|-------------|--------|-------|
| Section enters viewport | Fade + translate 8–16 px | 200–300 ms; once |
| Product image hover | Scale | ≤ 1.03; 200 ms |
| Product card hover | Elevation + translate | ≤ 2 px |
| Gallery change | Crossfade/slide | ≤ 250 ms |
| Sticky header | Compact + shadow | ≤ 200 ms |
| Skeleton | Subtle shimmer | Disable under reduced motion |

Rules:

- Animate only `opacity` and `transform` for decorative motion.
- No scroll-jacking, mandatory parallax, or cursor-following effects.
- `prefers-reduced-motion` removes non-essential transitions.
- Motion must not delay input or content visibility.

---

## 12. Merchant Customization Contract

Every major section groups settings consistently:

| Group | Typical controls |
|-------|------------------|
| Content | Heading, subheading, body, buttons, badges |
| Data source | Collection, products, content type, recommendation strategy |
| Style | Color scheme, typography role, alignment, width, spacing |
| Media | Desktop image, mobile image, video poster, focal point |
| Behavior | Sticky, carousel, autoplay off by default, reveal animation |
| Visibility | Breakpoint visibility, schedule/release, customer segment |

### 12.1 Guardrails

- Presets produce polished results before customization.
- Setting ranges remain curated; unrestricted arbitrary CSS is prohibited.
- Contrast, image weight, heading hierarchy, and CTA conflicts validate before publish.
- Advanced controls use progressive disclosure.
- Merchant content persists independently from theme package code.

---

## 13. Performance Is Visual Quality

Agency-quality design is rejected if it misses Volume 4 Chapter 12 budgets.

| Requirement | Target |
|-------------|--------|
| Mobile LCP p75 | ≤ 2.0 s (Nigeria profile) |
| Initial storefront JS | ≤ 100 KB gzip |
| Total first load | ≤ 800 KB |
| Hero image | ≤ 200 KB responsive AVIF/WebP |
| Layout shift | CLS ≤ 0.05 |
| Search typeahead | Results ≤ 300 ms after debounce |

Implementation rules:

- Responsive `srcset` and explicit width/height for all media
- Server-rendered product names, prices, category links, and trust evidence
- Lazy-load below-the-fold images, video players, social feeds, and AI runtime
- Reserve final dimensions for personalized content and skeletons
- Replace heavy Instagram SDK embeds with server-fetched, cached media tiles

---

## 14. Visual QA Matrix

Every built-in and marketplace theme must provide golden screenshots for:

| Route | Mobile | Desktop | Required states |
|-------|--------|---------|-----------------|
| Homepage | 320, 375, 414 | 1280, 1440 | Standard, personalized, loading |
| Collection | 320, 375 | 1280 | Filters open, empty, sale |
| Product | 320, 375 | 1280 | In stock, variant error, sold out |
| Search | 320 | 1280 | Suggestions, no results |
| Cart | 320 | 1280 | Empty, populated, error |
| AI assistant | 320 | 1280 | Embedded, expanded, no results |

Automated visual regression does not replace manual review of hierarchy, crop quality, and five-second comprehension.

---

## 15. Acceptance Criteria

- [ ] Above-the-fold composition passes the five-second storefront test.
- [ ] Homepage has one dominant primary CTA and at least one factual trust signal.
- [ ] Product Card supports hover, focus, touch, loading, sale, and sold-out states.
- [ ] PDP has responsive gallery/purchase composition and mobile sticky Add to Cart.
- [ ] Mega menu is keyboard accessible and becomes drill-down navigation on mobile.
- [ ] Mobile bottom navigation never obscures cart, consent, AI, or purchase controls.
- [ ] AI product finder is available as an embedded section, not only a floating widget.
- [ ] Personalization includes reason labels, controls, and non-personalized fallbacks.
- [ ] Reference themes cover Retail, Food, Services, Education, and Digital families.
- [ ] Theme differentiation review checks composition, type, media, and sections—not color alone.
- [ ] Motion respects reduced-motion and does not exceed the documented budget.
- [ ] Nigeria storefront fixtures display NGN; Kenya fixtures display KES.
- [ ] All golden routes pass performance, accessibility, and visual-regression gates.

---

## References

- [Chapter 01 — Design Philosophy](./01-design-philosophy-and-principles.md)
- [Chapter 06 — Core Component Inventory](./06-core-component-inventory.md)
- [Chapter 08 — Storefront & Checkout UX](./08-storefront-and-checkout-ux.md)
- [Chapter 12 — Performance & UX Budgets](./12-performance-and-ux-budgets.md)
- [Volume 6 Ch. 12 — Storefront Engine Eight Layers](../06-theme-engine/12-storefront-engine-eight-layers.md)
- [Volume 6 Ch. 11 — Reference Themes & Section Catalog](../06-theme-engine/11-reference-themes-section-catalog.md)
- [Volume 9 Ch. 05 — Shopping Assistant](../09-ai-platform/05-shopping-assistant-agent.md)
