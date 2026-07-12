# Experience Layer — App Roadmap & Research Alignment

**Document ID:** SCP-ARCH-EXP-001  
**Version:** 1.0.0  
**Status:** Active  
**Traceability:** ADR-023, Platform OS Ch. 13, Vol 15, Vol 12, Vol 8, Vol 4 Ch. 14

---

## Terminology

| Term | Meaning |
|------|---------|
| **Layer 0** (ADR-023) | Same as **Experience Layer** / **Presentation Layer** — all user-facing products under `apps/` |
| **Platform/** | Backend kernel services — **no UI** |
| **Modules/** | Business capabilities (Commerce, CRM…) — **API only** |
| **Connectors/** | External integrations — **no UI** |

We keep `Layer 0` in ADR-023 for traceability; **Experience Layer** is the preferred product name in docs and roadmaps.

---

## Is the research true? Are we missing anything?

**Short answer:** The research is **directionally correct** for a 10-year Shopify-scale platform. We are **not missing it from the vision** — it is **phased**. Nigeria GA (Phase 1) intentionally ships four experience products + API backend; the rest is H2–H5 in `V2.0/docs/`.

| Research item | In V2.0 specs? | SCP Phase | Status |
|---------------|----------------|-----------|--------|
| `apps/` not `resources/` | ADR-023 | Sprint 0 | **Done** |
| Storefront, admin, platform, marketing | Vol 4, 6, 16 | Phase 1 | **Done** (functional UI) |
| Theme builder / studio | Vol 6 Ch. 13 | Phase 2–3 | Planned (`apps/studio/`) |
| Customer portal (≠ storefront) | Vol 4 Ch. 14 | Phase 2 | Planned (`apps/customer/`) |
| Vendor portal | Vol 8 Marketplace | Phase 3 | Planned (`apps/vendor/`) |
| Developer portal | Vol 12 | Phase 3 | Planned (`apps/developers/`) |
| Partner portal | Vol 15 Enterprise | Phase 3–5 | Planned (`apps/partners/`) |
| Support portal | Vol 14 Ops, ADR-010 impersonation | Phase 2 | Planned (`apps/support/`) |
| AI Studio | Vol 9 | Phase 2+ | Planned (`apps/ai-studio/`) |
| Marketplace app (discover extensions) | Vol 6 Ch. 07, Vol 12 | Phase 3 | Planned (`apps/marketplace/`) |
| Analytics app | Vol 14 | Phase 2 | Planned (`apps/analytics/`) |
| Mobile merchant/customer/driver/vendor | Vol 18 | Phase 4 | Planned |
| POS desktop/tablet/mobile | Vol 15, 18 | Phase 4 | Planned |
| Edge layer + API gateway | Vol 10 (Cloudflare), middleware | Phase 1 infra | **Partial** — tenant middleware + CF; dedicated gateway Phase 2 |
| UI Engine / section registry | Vol 6 Ch. 12–13 | Phase 2 | **Started** — `ThemeResolver`, `Themes/` |
| Shared design system | Vol 4 SDS | Phase 1–2 | **Started** — `apps/packages/ui` → `design-system` |
| Rename marketing → website | — | Phase 2 | Consider when CRM/campaigns ship |

**Phase 1 is not incomplete** — it is scoped to Nigeria GA per [Master Execution Plan §5.3](../docs/21-implementation-playbooks/00-master-execution-plan.md).

---

## Experience products (full map)

### Phase 1 — Nigeria GA (build now)

| App | Product name | Port | Status |
|-----|--------------|------|--------|
| `storefront/` | Shopper experience | 3000 | Functional |
| `admin/` | Merchant Operating System | 3001 | Functional |
| `platform-admin/` | Platform Operations Center | 3002 | Functional |
| `marketing/` | Public website + signup | 3003 | Functional |

### Phase 2 — Growth

| App | Product name | Spec |
|-----|--------------|------|
| `customer/` | Customer Experience Portal | Vol 4 Ch. 14 |
| `support/` | Internal support & diagnostics | Vol 14, ADR-010 |
| `analytics/` | Merchant & platform analytics | Vol 14 |
| `studio/` | Business Builder (theme/CMS start) | Vol 6 Ch. 13 |
| `website/` | Rename/evolve from `marketing/` when CRM ships | Vol 16 |

### Phase 3 — Platform

| App | Product name | Spec |
|-----|--------------|------|
| `developers/` | Developer portal | Vol 12 |
| `vendor/` | Vendor Commerce Center | Vol 8 |
| `marketplace/` | Extension discovery | Vol 6, 12 |
| `partners/` | Agency & reseller portal | Vol 15 Enterprise |

### Phase 4+ — Omnichannel

| App | Spec |
|-----|------|
| `mobile/merchant/`, `mobile/customer/`, … | Vol 18 |
| `pos/desktop/`, `pos/tablet/`, … | Vol 15, 18 |
| `ai-studio/` | Vol 9 (full platform) |

---

## Shared packages (frontend)

All under `apps/packages/` — **never** inside `Modules/` or `Connectors/`.

| Package | Phase | Status |
|---------|-------|--------|
| `ui/` → `design-system/` | 1–2 | Stub (`@sapphital/scp-ui`) |
| `icons/`, `hooks/`, `types/`, `utils/` | 2 | Planned |
| `auth/`, `forms/`, `tables/`, `charts/` | 2 | Planned |
| `editor/`, `theme/`, `analytics/`, `ai/` | 2–3 | Planned |

Backend shared code stays in `Packages/` (PHP) and `sdk/` (public SDKs, Phase 3).

---

## Request path (target architecture)

```text
Experience Layer (apps/*)
        ↓
Edge (Cloudflare — tenant, cache, WAF, rate limit)     ← Phase 1 partial
        ↓
API Gateway / Laravel shell (V2.0/scp)                 ← Phase 1 now
        ↓
Platform Services (Platform/*)
        ↓
Modules (Modules/*)
        ↓
Connectors + Infrastructure
```

---

## Rules (do not break)

1. **No UI in backend packages** — screens only in `apps/`.
2. **One product = one app folder** — each gets its own roadmap and release cycle.
3. **Phase 1 flow unchanged** — signup → catalog → cart → Paystack → order → shipment.
4. **Scaffold future apps with README only** until their phase gate opens.

---

## Related

- [ARCHITECTURE-UI.md](./ARCHITECTURE-UI.md)
- [Master Execution Plan §5](../docs/21-implementation-playbooks/00-master-execution-plan.md)
- [Platform OS Ch. 13](../docs/03-architecture/13-platform-os-architecture.md)
