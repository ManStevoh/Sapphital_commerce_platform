# scp-terminal — Terminal Tech

**Theme ID:** `scp-terminal`  
**Version:** 0.1.0  
**Spec:** [Vol 6 — Theme Engine](../../docs/06-theme-engine/README.md) · [ADR-003](../../docs/00-meta/adr/003-theme-engine-react-json-schema.md)

Phase 1 reference theme for tech and electronics retail. Dark, high-contrast palette suited for spec-heavy product pages and gadget storefronts.

## Design tokens

| Token | Default | Notes |
|-------|---------|-------|
| Primary | `#0D1B2A` | Deep navy — modern, technical |
| Secondary | `#1B263B` | Lighter navy — layered depth |
| Accent | `#415A77` | Steel blue — interactive highlights |
| Background | `#0D1B2A` | Dark canvas — reduces eye strain |
| Foreground | `#E0E1DD` | Light gray — high-contrast body text |

## Package contents

| File | Purpose |
|------|---------|
| `theme.json` | Theme manifest (id, version, templates, sections) |
| `settings.schema.json` | Merchant-editable settings JSON Schema |
| `defaults.json` | Default setting values for new installations |

## Merchant settings (Phase 1)

- `primary_color` — brand colour (hex)
- `font_heading` — heading font family
- `logo_url` — optional logo CDN URL

## API consumption

Active theme config is served per tenant:

```
GET /api/v1/commerce/storefront/theme
X-Tenant-ID: {tenant_uuid}
```

Available themes are listed publicly:

```
GET /api/v1/commerce/storefront/themes
```

The `ThemeResolver` loads this package from `Themes/scp-terminal/` using the tenant's `settings.theme_id`.

## Templates (Phase 1 routes)

| Template | Storefront route |
|----------|------------------|
| `index` | `/` |
| `product` | `/products/{id}` |
| `cart` | `/cart` |
| `checkout` | `/checkout` |
