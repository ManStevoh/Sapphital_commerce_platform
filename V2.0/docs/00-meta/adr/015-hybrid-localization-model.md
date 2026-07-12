# ADR-015: Hybrid Localization — Field-Level + Document-Level

**Date:** 2026-07-12  
**Status:** Accepted  
**Deciders:** Stephen Musyoka Makola  
**Volume Reference:** Volume 7 — CMS

## Context

SCP targets Nigeria (English primary; Hausa, Yoruba, Igbo growth), Kenya (English/Swahili), and future Francophone West Africa. Localization must cover theme settings, structured content fields, URLs, and SEO (`hreflang`). Pure field-level i18n (each field stores locale map) works for products but is awkward for full blog posts; pure document-level duplication (separate entry per locale) explodes management overhead.

## Decision

**Adopt a hybrid localization model:**

1. **Field-level** — Default for structured content types: translatable fields store `{ "en": "...", "ha": "..." }` in JSONB with fallback chain `requested → tenant_default → en`.
2. **Document-level** — For `BlogPost`, `Page` (when merchant opts in), and `Learning` lesson bodies: separate entries linked by `translation_group_id` with shared slug prefix and locale suffix (`/ha/blog/...`).
3. **Theme settings** — Section setting strings use field-level locale maps; theme JSON schema marks translatable keys.
4. **URL strategy** — Subpath routing: `{store}/en/...`, `{store}/ha/...`; default locale omits prefix when configured.
5. **SEO** — Auto-generate `hreflang` alternates from translation groups; canonical URL per locale.
6. **Commerce catalog** — Products use field-level for title/description; variants/SKUs locale-agnostic (Volume 5).
7. **Fallback** — Missing translation displays fallback locale with `content-language` meta tag accurate to rendered locale.

## Alternatives Considered

| Alternative | Pros | Cons | Why Rejected |
|-------------|------|------|--------------|
| Field-only | Single entry | Poor for long-form blog/legal docs | Editor UX suffers |
| Document-only | Clear separation | Duplicate management; sync pain | Too heavy for product titles |
| External translation SaaS only | Professional quality | Cost for SMEs; offline workflow | Supplement, not primary model |
| No localization Phase 1 | Faster MVP | Blocks Kenya/WA expansion | Contradicts market strategy |

## Consequences

### Positive

- Practical for Nigerian merchants starting English-only, adding locales later
- hreflang and sitemap generation built-in (Volume 7 Ch. 06)
- Storefront API accepts `Accept-Language` and `?locale=` override
- Translation workflow hooks for future AI assist (Volume 9)

### Negative

- Two mental models for merchants (field vs document)
- Translation group integrity must be enforced in admin UI
- Search indexing per locale increases index size

### Neutral

- Machine translation suggestions optional; human publish required for legal pages
- RTL not required Phase 1; schema leaves room for Arabic (future)

## Compliance Notes

- NDPA/Kenya DPA: locale choice does not change data residency (ADR-011)
- Legal pages require explicit locale publish; no auto-translate for Terms/Privacy

## References

- [Volume 7 Ch. 02 — Content Model](../../07-cms/02-content-model.md)
- [Volume 7 Ch. 06 — SEO and Metadata](../../07-cms/06-seo-and-metadata.md)
- ADR-012, ADR-013
