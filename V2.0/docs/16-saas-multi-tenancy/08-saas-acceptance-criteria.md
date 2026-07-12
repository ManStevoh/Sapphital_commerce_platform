# Chapter 08: SaaS Acceptance Criteria

**Document ID:** SCP-SAAS-001-08  
**Version:** 1.0.0  
**Status:** ✅ Active  
**Traceability:** PRD-003, NFR-040, NFR-083, Volume 13 Ch. 10

---

## Purpose

Define **acceptance criteria** for SCP's SaaS commercial layer — gating Nigeria GA and ongoing releases of billing, entitlements, and tenant lifecycle features.

## Scope

- Tenant provisioning and lifecycle
- Plans and entitlements enforcement
- Billing and Paystack collection
- Usage metering accuracy
- Feature flags and custom domains
- NDPA billing data handling

---

## 1. Tenant Provisioning

- [ ] Signup to trial admin accessible within 60s p95
- [ ] Default seed: NGN, Africa/Lagos, recommended launch theme by vertical, sample products
- [ ] Tenant isolation on all tenancy tables (RLS suite pass)
- [ ] Provisioning failure retries 3×; no orphan partial tenants

## 2. Lifecycle States

- [ ] State machine implemented: trial → active → past_due → suspended → deleted
- [ ] Trial 14 days without card; one 7-day extension path
- [ ] Suspended tenant storefront offline within 5 min
- [ ] Hard delete at 90 days with export offered at cancel
- [ ] Data export completes within 48h (NDPA)

## 3. Plans & Entitlements

- [ ] Starter, Growth, Pro plans with NGN pricing live
- [ ] All entitlement keys in Chapter 03 enforced in API
- [ ] Quota exceed returns 402 with upgrade URL
- [ ] Upgrade immediate; downgrade end of period
- [ ] Transaction fee calculated and visible on dashboard

## 4. Billing & Invoicing

- [ ] Paystack recurring charge for subscription
- [ ] Webhook `charge.success` reconciles invoice paid
- [ ] Dunning day 0/3/7/14 with suspend at day 14
- [ ] PDF invoice with VAT line when merchant VAT registered
- [ ] Manual bank transfer application within 48h documented ops path

## 5. Usage Metering

- [ ] API, AI tokens, storage, GMV meters recording hourly
- [ ] Usage dashboard shows current period vs plan limits
- [ ] Overage invoice lines for AI and storage when enabled
- [ ] 80% quota email notification sent

## 6. Feature Flags

- [ ] Plan entitlements exposed as feature flags
- [ ] Kill switch `global.checkout` tested in staging
- [ ] Kill switch change requires MFA + audit log
- [ ] Beta percentage rollout deterministic by tenant_id

## 7. Custom Domains

- [ ] Growth plan: 1 custom domain attachable
- [ ] DNS TXT + CNAME verification flow works
- [ ] SSL active within 15 min p95 after DNS correct
- [ ] Host header resolves correct tenant (isolation test)

## 8. Security & Compliance

- [ ] Billing PII encrypted at rest
- [ ] Paystack authorization tokens not in logs
- [ ] RoPA includes platform billing as processing activity
- [ ] Impersonation audit for billing support actions

## 9. Testing Evidence

- [ ] E2E: signup → subscribe Growth → invoice paid
- [ ] E2E: failed payment → dunning → suspend
- [ ] E2E: quota block at product limit
- [ ] Integration: Paystack webhook idempotency

## 10. Platform Admin & Marketing Site (P2)

Per [Ch. 11](./11-platform-admin-operator-guide.md)–[Ch. 14](./14-localization-admin-and-translations.md):

- [ ] E2E: marketing signup → plan checkout (FSL) → TPE → live store URL
- [ ] Platform Admin: tenant list, suspend/reactivate, failed provisioning retry
- [ ] Platform coupon applied at signup checkout (Ch. 13)
- [ ] Testimonials, brands, topbar managed in Platform Admin (Ch. 12)
- [ ] Custom domain queue without cPanel (Ch. 07, 11)
- [ ] Impersonation with MFA + audit (ADR-010)
- [ ] Translation string editor for merchant locales (Ch. 14)
- [ ] **Buyer wallet for plan checkout omitted** — FSL redirect only (documented)

---

**Sign-off roles:** Lead Architect, Product lead, Finance operations lead, DPO (Nigeria).

Volume 16 is **complete for Phase 1 Nigeria launch** when all criteria above pass.

---

## References

- [Volume 13 Ch. 10 — Release Criteria](../13-testing/10-release-criteria.md)
- [Volume 11 Ch. 07 — Security Acceptance](../11-security/07-acceptance-criteria.md)
- [Chapter 02 — Tenant Lifecycle](./02-tenant-lifecycle.md)
- [Chapter 11 — Platform Admin Operator Guide](./11-platform-admin-operator-guide.md)
- [Chapter 12 — Platform Marketing Site & Signup](./12-platform-marketing-site-and-signup.md)
