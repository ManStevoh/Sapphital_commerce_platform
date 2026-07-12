# ADR-013: BlockNote as Canonical Rich-Text Format

**Date:** 2026-07-12  
**Status:** Accepted  
**Deciders:** Stephen Musyoka Makola  
**Volume Reference:** Volume 7 — CMS

## Context

SCP requires rich text for blog posts, lesson bodies, policy pages, and inline content within structured fields. Options include storing HTML, Markdown, ProseMirror/Tiptap JSON, or BlockNote JSON. HTML is XSS-prone if rendered unsafely; Markdown lacks structured blocks for embeds and callouts; multiple formats would fragment the editor stack.

Merchants in Nigeria often edit from mobile browsers; the editor must be touch-friendly and load quickly on constrained networks (NFR-001).

## Decision

**BlockNote JSON is the canonical storage format for all rich-text fields in SCP CMS.**

1. **Storage** — Persist BlockNote document JSON in PostgreSQL `jsonb` columns with schema version tag.
2. **Editor** — BlockNote React editor in admin; block palette limited to approved types (paragraph, heading, list, image, video embed, callout, code, divider).
3. **Rendering** — Server-side React renderer maps BlockNote blocks to theme-safe components; no `dangerouslySetInnerHTML` for merchant content.
4. **Sanitization** — Block allowlist at save and render; external embeds via oEmbed proxy (Volume 11 SSRF controls).
5. **Export** — HTML and plain-text export generated on demand for email/RSS; source of truth remains BlockNote JSON.
6. **Migration** — Import pipelines convert Markdown/HTML to BlockNote on ingest with validation report.

## Alternatives Considered

| Alternative | Pros | Cons | Why Rejected |
|-------------|------|------|--------------|
| HTML storage | Universal | XSS risk; inconsistent editing; hard to validate | Security and consistency |
| Markdown | Simple, portable | Weak block model; poor WYSIWYG for SMEs | Insufficient for lesson/blog UX |
| Tiptap/ProseMirror raw JSON | Flexible | Lower-level API; more custom work for block UX | BlockNote built on ProseMirror with better defaults |
| Lexical | Meta-backed | Newer ecosystem; fewer commerce examples | Team chose BlockNote for Notion-like UX parity |

## Consequences

### Positive

- Consistent editor experience across blog, lessons, and CMS fields
- Type-safe block rendering in React theme components
- Easier AI-assisted content (block-level suggestions) in Volume 9
- Version diffs at block granularity

### Negative

- BlockNote version upgrades require migration testing
- Theme developers cannot inject custom arbitrary blocks without platform review
- RSS/email consumers need HTML export layer

### Neutral

- Search indexing extracts plain text from BlockNote JSON server-side
- Full-text search uses PostgreSQL `tsvector` on extracted text

## Compliance Notes

- XSS prevention aligns with OWASP ASVS Level 2
- Media blocks reference tenant-scoped media library URLs with signed access where required

## References

- [Volume 7 Ch. 04 — Block Library](../../07-cms/04-block-library.md)
- [Volume 7 Ch. 07 — Blog and Navigation](../../07-cms/07-blog-and-navigation.md)
- ADR-012, ADR-003
