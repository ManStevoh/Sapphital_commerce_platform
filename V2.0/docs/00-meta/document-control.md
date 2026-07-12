# Document Control

## Document Identity

| Field | Value |
|-------|-------|
| **Project** | SAPPHITAL Commerce Platform (SCP) |
| **Document Set** | 1.0 Architecture Specification |
| **Owner** | Sapphital Learning Company |
| **Lead Architect** | Stephen Musyoka Makola |
| **Repository** | `marketplae_sapphital/V2.0` |
| **Classification** | Internal Engineering Reference |

## Version History

| Version | Date | Author | Summary |
|---------|------|--------|---------|
| 0.1.0 | 2026-07-12 | Stephen Musyoka Makola | Initial architecture program scaffold |
| 1.0.0 | 2026-07-12 | Stephen Musyoka Makola | Volume 0 (Meta) and Volume 1 (Vision) baseline |
| 1.1.0 | 2026-07-12 | Stephen Musyoka Makola | Added 20-track research and synthesis program for enterprise-scale specification |
| 1.2.0 | 2026-07-12 | Stephen Musyoka Makola | All 20 specialist research tracks complete; synthesis status tracker added |
| 1.3.0 | 2026-07-12 | Stephen Musyoka Makola | Nigeria-first strategy; ADR 004–011; Volume 11 Security synthesized |
| 1.4.0 | 2026-07-12 | Stephen Musyoka Makola | Multi-agent synthesis Volumes 2–16 in progress; sidebar expanded |
| 1.5.0 | 2026-07-12 | Stephen Musyoka Makola | All 16 volumes synthesized (~150+ chapters); Nigeria-first compliance |
| 1.6.0 | 2026-07-12 | Stephen Musyoka Makola | Volumes 17–21 complete; ADRs 012–016; Volume 15 expanded to 14 chapters |
| 1.7.0 | 2026-07-12 | Stephen Musyoka Makola | Storefront visual direction, reference theme portfolio, section catalog, portability and conversion UX contracts |
| 1.8.0 | 2026-07-12 | Stephen Musyoka Makola | Storefront Engine eight layers; ADR-017/018; AI storefront commerce, ASI, theme generator; community/loyalty/live commerce |
| 1.9.0 | 2026-07-12 | Stephen Musyoka Makola | Commerce Infrastructure for Africa strategy; ADR-019 Financial Services Layer; Vol 5 Ch. 16–19; Africa AI advisor; gateway adapter catalog |
| 2.0.0 | 2026-07-12 | Stephen Musyoka Makola | ADR-020 SAPPHITAL Intelligence Platform; AI OS seven layers; Vol 9 Ch. 17–22; three-platform ecosystem; product principle 4 revised |
| 2.1.0 | 2026-07-12 | Stephen Musyoka Makola | ADR-021 AI-guided onboarding; Vol 16 Ch. 09; Vol 4 Ch. 15; launch readiness + journey updates |
| 2.2.0 | 2026-07-12 | Stephen Musyoka Makola | ADR-022 Tenant Provisioning Engine; multi-store, wildcard DNS, async saga, entitlements-driven limits |
| 2.3.0 | 2026-07-12 | Stephen Musyoka Makola | ADR-023 Platform OS; Vol 3 Ch. 13; Products/Services/Connectors layout; bounded context package map |
| 2.4.0 | 2026-07-12 | Stephen Musyoka Makola | Platform OS complete inventory audit; Layer 0 clients, expanded kernel/services/connectors/extensions, Packages/, canonical placement |
| 2.5.0 | 2026-07-12 | Stephen Musyoka Makola | Engineering Knowledge Base; doc-first Cursor workflow; module template; standards; knowledge graph; Architect AI governance; Cursor rules |
| 2.6.0 | 2026-07-12 | Stephen Musyoka Makola | Legacy platform gap-fill — Platform Admin E2E (Vol 16 Ch. 11–14), campaigns/catalog ops (Vol 5 Ch. 20–21), integrations hub + WooCommerce (Vol 19 Ch. 11–12), forms (Vol 7 Ch. 11), mobile/POS (Vol 18 Ch. 11–12), merchant analytics (Vol 14 Ch. 12); capability matrix |
| 2.7.0 | 2026-07-12 | Stephen Musyoka Makola | Buyer wallet + service listings decisions formalized; Vol 5 Ch. 22 Bookings extension; launch checklist SaaS gates; PayPal global payments |
| 2.8.0 | 2026-07-12 | Stephen Musyoka Makola | Vol 21 Ch. 00 Master Execution Plan — single all-phases build document, volume mapping, Cursor workflow, legacy feature phase table |
| 2.9.0 | 2026-07-12 | Stephen Musyoka Makola | Removed vendor product names from spec; legacy capability matrix renamed; neutral legacy platform terminology |

## Review Cycle

| Activity | Frequency | Responsible |
|----------|-----------|-------------|
| Architecture review | Monthly | Lead Architect |
| Security review | Quarterly | Security lead (TBD) |
| Full specification audit | Semi-annual | Engineering leadership |
| ADR review | Per decision | Decision author + architect |

## Change Control Process

1. **Propose** — Open issue or branch describing the change and affected volumes
2. **Impact analysis** — Identify downstream volumes, ADRs, and modules affected
3. **Author** — Update Markdown source in `docs/`
4. **Review** — At least one peer review for architectural changes
5. **Record** — Update this version table and relevant ADRs
6. **Publish** — Merge to `main`; CI rebuilds docs site

## Document Conventions

### File Naming

- Volumes: `docs/NN-topic/` (zero-padded number)
- Chapters: `NN-descriptive-name.md` (ordered within volume)
- ADRs: `docs/00-meta/adr/NNN-short-title.md`

### Diagrams

All diagrams use **Mermaid** syntax embedded in Markdown for version control and automated rendering.

### Status Labels

| Label | Meaning |
|-------|---------|
| ✅ Active | Approved and current |
| 📝 Draft | Work in progress |
| 🔲 Planned | Not yet authored |
| ⚠️ Deprecated | Superseded; retained for history |
| 🔄 Under Review | Pending approval |

### Traceability

Every requirement in Volume 1+ uses IDs:

- **FR-** Functional requirements
- **NFR-** Non-functional requirements
- **PRD-** Product requirements
- **ADR-** Architecture decisions

Subsequent volumes must reference these IDs when implementing features.

## Standards Alignment

This specification aligns with the following frameworks (compliance levels noted per volume):

| Standard | Scope | Target Level |
|----------|-------|--------------|
| ISO/IEC/IEEE 42010 | Architecture description | Full alignment |
| ISO/IEC 25010 | Software quality model | NFR mapping |
| C4 Model | Architecture diagrams | All levels |
| OWASP ASVS | Application security | Level 2 (5.0) |
| Nigeria NDPA + GAID | Data protection (primary market) | Full compliance Phase 1 |
| Kenya DPA | Data protection (Kenya launch) | Full compliance at KE launch |
| WCAG 2.2 | Accessibility | Level AA |
| OpenAPI 3.1 | API specification | All public APIs |
| PCI DSS | Payment card data | SAQ A (hosted checkout) |
| Diátaxis | Documentation structure | Full adoption |

## Distribution

| Audience | Access | Format |
|----------|--------|--------|
| Internal engineering | Git repository | Markdown + Docusaurus site |
| External developers | Public GitHub Pages (planned) | Docusaurus site |
| Partners / investors | Executive summaries | PDF export from Volume 1 |
