# Chapter 12: Acceptance Criteria

**Document ID:** SCP-MKT-001-12  
**Version:** 1.0.0  
**Status:** ✅ Active  

---

Volume 8 Marketplace is **complete for Phase 1 Nigeria launch** when all criteria below pass. Criteria are grouped by domain and mapped to test types.

---

## 1. Marketplace Activation

- [ ] Marketplace mode can be enabled on a store without affecting single-vendor stores on the same tenant
- [ ] Store setting `marketplace.enabled = true` exposes vendor application and multi-vendor checkout
- [ ] Disabling marketplace mode blocks new vendor onboarding; existing vendors enter read-only state

## 2. Vendor Onboarding & KYC

- [ ] Operator invitation flow delivers email with 72h expiring link
- [ ] Public vendor application respects rate limit (5/hour/IP)
- [ ] Nigeria individual KYC requires NIN, BVN, ID document; business requires CAC
- [ ] Duplicate BVN in same store blocked at submission
- [ ] Bank verification via Paystack Resolve returns account name match score
- [ ] KYC documents encrypted at rest; plain-text NIN/BVN never appears in logs
- [ ] Operator approve triggers PSP subaccount provisioning job
- [ ] Vendor reaches `active` only after PSP subaccount exists
- [ ] Rejected vendor cannot re-submit within 24h cooldown
- [ ] Bank account change requires MFA and triggers 72h payout hold

## 3. NDPA Vendor Data Walls

- [ ] Automated isolation suite: **0** cross-vendor reads across API and direct DB (RLS) for products, orders, KYC, disputes, commissions
- [ ] Vendor fulfillment view shows masked customer email by default
- [ ] Vendor API returns 403 when `vendor_id` in token does not match resource
- [ ] KYC document URLs expire after 15 minutes
- [ ] Rejected application data purged after 90 days (job verified)
- [ ] Marketplace DPIA checklist signed by DPO before GA

## 4. Multi-Vendor Catalog

- [ ] Product creation on marketplace store requires `vendor_id`
- [ ] Pre-approval moderation prevents unpublished products appearing in search/storefront
- [ ] Trusted vendor (score ≥ 85) skips pre-approval when mode enabled
- [ ] Vendor A cannot update Vendor B product (403)
- [ ] Suspended vendor products removed from search index within 60 seconds
- [ ] Bulk import handles 200 SKUs within 60 seconds

## 5. Commissions & Fees

- [ ] Multi-vendor order produces per-vendor commission rows summing to operator expectation
- [ ] Commission rate snapshotted at order placement; post-order tier change has no effect
- [ ] 50% partial refund reverses 50% commission
- [ ] All monetary values integer kobo; no float arithmetic in commission paths
- [ ] Vendor portal commission breakdown matches ledger within 1 kobo

## 6. Split Payments & Naira Payouts

- [ ] Multi-vendor checkout initializes Paystack payment with split shares summing to total kobo
- [ ] Flutterwave secondary path documented and tested in staging
- [ ] `charge.success` webhook idempotent on replay (no double credit)
- [ ] Return window hold prevents commission release until elapsed
- [ ] Weekly payout batch creates transfer to verified NUBAN
- [ ] Failed payout retries 3× then enters manual review queue
- [ ] Daily reconciliation job alerts on variance > ₦1.00
- [ ] PCI SAQ A maintained — no card data touches SCP servers (ADR-004)

## 7. Vendor Portal

- [ ] Dashboard KPIs match analytics projection within 5-minute lag
- [ ] Vendor staff role restrictions enforced on all routes
- [ ] Mobile layout passes WCAG 2.2 AA spot check on order fulfillment flow
- [ ] Payout page shows hold reasons in plain language
- [ ] Suspended vendor redirected to notice page; cannot create products

## 8. Disputes & Trust

- [ ] Customer can open dispute within 14-day eligibility window
- [ ] Vendor 48h SLA auto-escalates to operator review
- [ ] Open dispute places disputed amount in `held_balance`
- [ ] Trust score recalculates daily per documented formula
- [ ] Critical strike auto-suspends vendor
- [ ] Three major strikes in 180 days triggers auto-suspension

## 9. State Machines

- [ ] Invalid transitions throw and do not persist state
- [ ] Every transition recorded in `status_transitions` with actor
- [ ] Commission cannot reach `released` while dispute is open
- [ ] Payout line cannot exceed 3 retries from `failed`

## 10. API & Events

- [ ] OpenAPI 3.1 spec covers all operator and vendor endpoints in Chapter 10
- [ ] All listed domain events emit via outbox with `tenant_id` and `store_id`
- [ ] `Idempotency-Key` prevents duplicate payout batch creation
- [ ] Authorization matrix tests cover 100% of marketplace routes
- [ ] Operator webhooks fire on `marketplace.payout.completed` with valid HMAC

## 11. Analytics

- [ ] Vendor overview gross sales matches sum of paid sub-orders ± 1 kobo
- [ ] Payout reconciliation equation balances (opening + earnings − refunds − payouts = closing)
- [ ] Operator marketplace dashboard shows all vendors; vendor dashboard scoped to self
- [ ] CSV export completes within 30 seconds for 1 year daily rollups

## 12. Compliance & Tax

- [ ] VAT breakdown stored on paid orders with 7.5% default rate snapshot
- [ ] Operator VAT export includes vendor attribution columns
- [ ] Vendor terms acceptance version stored at onboarding
- [ ] RoPA includes marketplace processing activities
- [ ] WHT deduction disabled by default until operator explicitly enables

## 13. Performance & Observability

- [ ] Vendor dashboard API p95 ≤ 300ms under load test (50 concurrent vendors)
- [ ] Commission calculation on OrderPaid p95 ≤ 100ms (10 vendor lines)
- [ ] Metrics `marketplace.payout.failed` and `marketplace.cross_vendor_access_blocked` instrumented
- [ ] Alert fires when cross-vendor access blocked count > 0

## 14. End-to-End Scenario (Nigeria Golden Path)

Manual or automated E2E must pass:

1. Operator enables marketplace on Lagos fashion store (NGN)
2. Operator invites vendor; vendor completes KYC with GTBank NUBAN
3. Operator approves; Paystack subaccount provisioned
4. Vendor publishes product; operator approves listing
5. Customer buys from two vendors in single checkout; Paystack redirect payment succeeds
6. Split allocated correctly; commissions accrued
7. Vendor marks shipped; delivery confirmed; hold period passes
8. Commission released; payout batch pays vendor NGN balance
9. Customer opens dispute on one line; hold applied; operator resolves
10. Vendor trust score updates; analytics reflect sale

---

## Sign-Off Roles

| Role | Responsibility |
|------|----------------|
| Lead Architect | Module design, state machines, API completeness |
| Product (Marketplace) | Jumia differentiation, vendor UX |
| Security reviewer | Isolation tests, KYC encryption, webhook verification |
| DPO | NDPA DPIA, RoPA, data walls |
| Legal (Nigeria) | VAT/WHT posture, vendor terms |
| QA Lead | E2E golden path, regression suite |

---

## Traceability

| Criterion Group | Requirements |
|-----------------|--------------|
| Onboarding | FR-006, PRD-008 |
| Isolation | NFR-040, NFR-083, NFR-085 |
| Payments | ADR-004, NFR-044 |
| Money | FR-021 |
| Residency | NFR-071, ADR-011 |
| Performance | NFR-003, NFR-004, NFR-006 |

---

**Volume 8 status:** Specification complete. Implementation teams may begin marketplace module build against these criteria.
