# Content CMS (`Modules/Content/Cms`)

Phase 2 CMS package per Volume 7 and ADR-012.

## Entities

| Model | Table | Purpose |
|-------|-------|---------|
| `Page` | `cms_pages` | Storefront / legal pages with SEO + `body_json` sections |
| `BlogPost` | `cms_blog_posts` | Blog entries with author, tags, featured image |
| `NavigationMenu` | `cms_navigation_menus` | Header/footer link lists |
| `ContentVersion` | `cms_content_versions` | Last 10 snapshots per page/post |

All models use `BelongsToTenant` + PostgreSQL RLS.

## API (prefix `/api/v1/content/cms`)

| Method | Path | Auth |
|--------|------|------|
| GET | `/health` | public |
| GET | `/pages`, `/pages/published`, `/pages/by-slug/{slug}` | tenant |
| POST/PUT/DELETE | `/pages`… | merchant + `cms.write` |
| GET/POST | `/pages/{id}/versions`, `/pages/{id}/versions/{versionId}/restore` | merchant |
| GET | `/blog-posts`, `/blog-posts/published`, `/blog-posts/by-slug/{slug}`, `/blog-posts/{id}/related` | tenant |
| GET | `/blog/feed.xml` | tenant |
| POST/PUT/DELETE | `/blog-posts`… | merchant + `cms.write` |
| GET/POST | `/blog-posts/{id}/versions`… | merchant |
| GET/PUT | `/navigation/{header\|footer}` | tenant / merchant |

Published-only for public slug lookups. Scheduled content is published/unpublished by `cms:process-scheduled-content`.

## Admin / Storefront

- Admin: section editor (drag-and-drop), live preview (desktop/mobile), SEO panel, schedule, version restore
- Storefront: section renderers, pagination, related posts, RSS, sitemap entries, Article/BreadcrumbList JSON-LD

## Next (Phase 2 §2+)

- Search / collections maturity
- Full theme section parity (hero, product-grid) beyond CMS content blocks
