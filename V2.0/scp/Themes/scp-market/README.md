# scp-market — Savanna Market

**Theme ID:** `scp-market`  
**Version:** 0.1.0  
**Spec:** [Vol 6 — Theme Engine](../../docs/06-theme-engine/README.md) · [ADR-003](../../docs/00-meta/adr/003-theme-engine-react-json-schema.md)

Phase 1 reference theme for general retail. Warm earth tones evoke open-air market browsing — ideal for fashion, home goods, and everyday essentials.

## Design tokens

| Token | Default | Notes |
|-------|---------|-------|
| Primary | `#8B4513` | Saddle brown — warmth, authenticity |
| Secondary | `#A0522D` | Sienna — complementary earth tone |
| Accent | `#D2691E` | Chocolate orange — call-to-action highlight |
| Background | `#F5E6D3` | Warm parchment — soft, inviting canvas |
| Foreground | `#3E2723` | Dark brown — readable body text |

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

The `ThemeResolver` loads this package from `Themes/scp-market/` using the tenant's `settings.theme_id`.

## Templates (Phase 1 routes)

| Template | Storefront route |
|----------|------------------|
| `index` | `/` |
| `product` | `/products/{id}` |
| `cart` | `/cart` |
| `checkout` | `/checkout` |
