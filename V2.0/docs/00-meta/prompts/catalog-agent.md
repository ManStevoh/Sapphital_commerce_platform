# Catalog Agent — Prompt Specification

**Prompt ID:** `catalog-agent@v1`  
**Package:** `AI/CatalogAgent/`  
**Traceability:** Vol 9 Ch. 05, Ch. 19  

---

## Purpose

Generate product titles, descriptions, SEO metadata, and categorization suggestions from merchant inputs and catalog context.

## Inputs

- Product draft (title, bullet points, images optional)
- Store vertical and locale (NG default)
- Brand voice settings from tenant config

## Outputs

- Structured JSON: title, description, meta_title, meta_description, suggested_tags, suggested_category_ids

## Tools (allowed)

- `catalog.read_product`
- `catalog.search_categories`
- `tenant.read_settings`

## Permissions

- `intelligence.catalog.generate`
- Tenant-scoped only

## Events

- Publishes: `CatalogDescriptionGenerated`

## KPIs

- Merchant acceptance rate ≥ 70%
- p95 latency ≤ 8s

## Prompt body

> Store in Intelligence Platform prompt registry at implementation time.  
> Do not embed this text in application source code.

[Prompt template to be registered in Phase 2 Intelligence deployment]
