# Chapter 14: Localization Admin & Translations

**Document ID:** SCP-SAAS-001-14  
**Version:** 1.0.0  
**Status:** ✅ Active  
**Traceability:** ADR-015, Vol 5 Ch. 18  
**Legacy mapping:** Tenant language + string translation editor

---

## Purpose

Merchant and platform **translation management UI** — complementing ADR-015 hybrid localization (database + files).

## Scope

- Enabled languages per store
- Translation groups (storefront, admin, email, SMS)
- String editor with fallback chain
- Import/export XLIFF/CSV for agencies
- AI-assisted translation (Vol 9 TranslationAgent)

## Out of Scope

- Machine translation without human review for legal strings

---

## 1. Language Configuration

| Level | Setting |
|-------|---------|
| Platform default | `en` |
| Store enabled | `en`, `ha`, `yo`, `ig`, `sw` (Phase 1.5+) |
| Fallback chain | `ha` → `en` |

---

## 2. Translation Groups

| Group | Examples |
|-------|----------|
| `storefront` | Add to cart, checkout labels |
| `admin` | Merchant admin UI |
| `email` | Order confirmation templates |
| `sms` | OTP and alert templates |
| `theme` | Theme setting labels |

---

## 3. Admin UI (Merchant)

**Settings → Languages → Translations**

- Side-by-side: source (en) | target
- Filter missing strings
- Mark reviewed
- Bulk AI translate (with review queue)

**Integrity rule (ADR-015):** same keys across groups; missing key falls back to default locale.

---

## 4. APIs

| Method | Path |
|--------|------|
| GET | `/api/v1/admin/translations?locale=ha&group=storefront` |
| PUT | `/api/v1/admin/translations/{key}` |

Storefront reads compiled cache per tenant+locale (Redis, TTL 1h).

---

## 5. Acceptance Criteria

- [ ] Merchant enables Hausa; missing strings fall back to English
- [ ] Email template translation used in Notifications engine
- [ ] Export/import CSV for agency workflow
- [ ] AI translate creates draft entries only until reviewed

---

## References

- [ADR-015](../00-meta/adr/015-hybrid-localization-model.md)
- [Vol 5 Ch. 18 — Regional engines](../05-commerce-engine/18-regional-engines-currency-tax-language.md)
