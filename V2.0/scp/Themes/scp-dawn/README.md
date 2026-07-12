# scp-dawn — Lagos Atelier

**Theme ID:** `scp-dawn`  
**Version:** 0.1.0  
**Spec:** [Vol 6 — Theme Engine](../../docs/06-theme-engine/README.md) · [ADR-003](../../docs/00-meta/adr/003-theme-engine-react-json-schema.md)

Phase 1 reference theme for Nigerian general retail. Optimized for mobile-first Lagos 4G, NGN pricing display, and Paystack checkout integration.

## Nigeria-first design tokens

| Token | Default | Notes |
|-------|---------|-------|
| Primary | `#1B4332` | Deep forest green — trust, growth |
| Secondary | `#2D6A4F` | Complementary green |
| Accent | `#E9C46A` | Warm gold highlight |
| Background | `#FEFAE0` | Warm cream — reduces glare on mobile |

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

The `ThemeResolver` loads this package from `Themes/scp-dawn/` using the tenant's `settings.theme_id` (default: `scp-dawn`).

## Templates (Phase 1 routes)

| Template | Storefront route |
|----------|------------------|
| `index` | `/` |
| `product` | `/products/{id}` |
| `cart` | `/cart` |
| `checkout` | `/checkout` |
