# ADR-003: Theme Engine — React Components with JSON Template Schema

**Date:** 2026-07-12  
**Status:** Accepted  
**Deciders:** Stephen Musyoka Makola  
**Volume Reference:** Volume 6 — Theme Engine

## Context

SCP requires a theme system allowing merchants to customize storefront appearance and third-party developers to build and sell themes — similar to Shopify's theme ecosystem but using modern web technologies.

Shopify uses Liquid (proprietary template language). Alternatives for SCP:

- **Liquid clone** — familiar to Shopify developers but proprietary and limited
- **Blade templates** — Laravel-native but server-rendered, poor for headless/SSR hybrid
- **React components + JSON schema** — modern, type-safe, SSR-compatible with Next.js
- **Web components** — framework-agnostic but immature ecosystem for commerce themes

## Decision

**Build the theme engine using React Server Components + JSON template schema**, where:

1. Themes are npm packages containing React components
2. Page structure is defined in JSON (sections, blocks, settings)
3. Merchants customize via a visual section/block editor
4. Theme SDK provides CLI for development, preview, and publishing
5. Next.js renders themes with ISR for performance

## Alternatives Considered

| Alternative | Pros | Cons | Why Rejected |
|-------------|------|------|--------------|
| Liquid template engine | Shopify familiarity, sandboxed | Proprietary, limited logic, no type safety, separate language to learn | Team expertise is React/TypeScript; want modern tooling |
| Blade + Livewire | Laravel-native, simple | Server-rendered only, no ISR, poor mobile performance, not headless-compatible | Violates performance and API-first principles |
| Pure JSON → HTML renderer | Simple, safe sandbox | Limited interactivity, no component reuse, hard to build rich UIs | Insufficient for Shopify-quality storefronts |
| Web Components | Framework-agnostic | Immature tooling, no SSR story, limited developer ecosystem | Too early; React ecosystem is proven |

## Consequences

### Positive

- Full React ecosystem for theme developers (npm, TypeScript, testing)
- SSR/ISR via Next.js — meets NFR-001 (LCP ≤ 2.0s)
- JSON schema enables visual editor without code changes
- Type-safe theme settings with Zod validation
- Theme preview sandbox without affecting live store
- Headless-compatible — same theme works with Storefront API

### Negative

- Theme developers must know React (higher barrier than Liquid)
- Theme bundle size must be managed (performance budget per theme)
- Server Components require Next.js App Router (no Pages Router)
- Theme validation and sandboxing more complex than template language

### Neutral

- Theme marketplace requires review process for published themes
- Migration tool needed if merchants switch themes (data mapping)

## Theme Architecture

```text
Theme Package Structure:
├── package.json          (theme metadata)
├── theme.schema.json     (settings definition)
├── templates/
│   ├── index.json        (homepage sections/blocks)
│   ├── product.json      (product page layout)
│   └── collection.json   (collection page layout)
├── sections/
│   ├── Hero.tsx
│   ├── ProductGrid.tsx
│   └── Footer.tsx
├── blocks/
│   ├── Heading.tsx
│   ├── Image.tsx
│   └── Button.tsx
└── assets/
    ├── styles/
    └── images/
```

```text
Rendering Flow:
JSON Template → Section Registry → React Components → SSR/ISR → HTML
                     ↑
              Theme Settings (merchant customization)
                     ↑
              Design Tokens (colors, fonts, spacing)
```

## Engineering Principles Impact

| Principle | Impact |
|-----------|--------|
| UX First | React enables rich microinteractions and animations |
| Performance | ISR caching; must enforce JS budget per theme |
| Extensible | Core extensibility mechanism for storefront |
| Decoupled | Themes are independent packages; core never imports theme code |
| API-First | Themes consume Storefront API; no direct DB access |

## Performance Implications

- Theme JS budget: ≤ 100 KB gzipped (stricter than platform budget)
- ISR revalidation: 60 seconds default for product pages
- Theme assets served via CDN (Cloudflare R2)
- Lazy load sections below fold
- Theme performance score must be ≥ 85 Lighthouse to publish to Theme Store

## Security Implications

- Themes run in sandboxed context — no direct API access
- Theme data comes through Storefront API only (read-only, scoped)
- No arbitrary JavaScript execution in merchant-customizable settings
- Theme review process before Theme Store publication
- CSP prevents inline scripts from themes

## Operational Implications

- Theme CLI: `scp-theme init`, `scp-theme dev`, `scp-theme publish`
- Theme preview server for development
- Theme versioning with semver
- Theme Store with review workflow (automated + manual)

## Migration Path

- Phase 1: 3 built-in themes (hardcoded, no SDK)
- Phase 2: Theme customization (colors, logo, fonts via settings)
- Phase 3: Full Theme SDK, section/block editor, Theme Store
- Phase 3+: Third-party theme developer ecosystem

## References

- Shopify Online Store 2.0 (sections/blocks architecture)
- Next.js App Router and Server Components
- shadcn/ui component patterns
- Shopify Hydrogen (React-based Shopify storefront framework)
