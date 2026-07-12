# Chapter 11: Forms & Lead Capture

**Document ID:** SCP-CMS-001-11  
**Version:** 1.0.0  
**Status:** ✅ Active  
**Traceability:** ADR-012, Vol 19 CRM-lite  
**Legacy mapping:** Form builder / widget builder forms

---

## Purpose

**Form builder** for merchant storefronts — contact, quote, wholesale inquiry — submissions to email, CRM-lite, and optional webhooks.

## Scope

- Form definitions (fields, validation)
- Embed as CMS section `contact-form`
- Submission storage and notification
- Turnstile spam protection

## Out of Scope

- Payment forms (checkout only)

---

## 1. Entity

**FormDefinition** — `id`, `tenant_id`, `name`, `fields_json`, `success_message`, `notify_emails[]`

**FormSubmission** — `form_id`, `payload_json`, `ip_hash`, `submitted_at`

---

## 2. Field Types

text, email, phone, textarea, select, checkbox, file (max 5MB, allowlist mime)

---

## 3. Actions on Submit

1. Store submission (tenant-scoped RLS)
2. Email merchant via Notifications
3. Create CRM-lite lead (Vol 19 Ch. 05)
4. Optional webhook (Vol 12)

---

## 4. Section Schema

`contact-form` section references `form_id` — rendered by storefront (Vol 6).

---

## 5. Acceptance Criteria

- [ ] Turnstile required on public forms
- [ ] File upload scanned and size-limited
- [ ] Submissions export CSV
- [ ] SSRF-safe webhook URLs (Vol 12)

---

## References

- [Ch. 01 — CMS Overview](./01-cms-overview.md)
- [Vol 19 Ch. 05 — CRM Lite](../19-automation-integrations/05-crm-lite.md)
