# Chapter 10: Mobile & POS Acceptance Criteria

**Document ID:** SCP-MOB-018-10  
**Version:** 1.0.0  
**Status:** ✅ Active  
**Traceability:** FR-MOB-001–004, FR-POS-001–012, NFR-001, NFR-003, NFR-012, NFR-040, NFR-044, NFR-051, NFR-071, NFR-083, PRD-005, PRD-014

---

## Purpose

Define **launch and regression acceptance criteria** for Volume 18 — gating Nigeria Phase 1 mobile and POS release, Lagos retail pilot sign-off, and Kenya M-Pesa POS expansion gate.

## Scope

- Customer shopping app (Android)
- Merchant admin app (Android)
- POS register app (Android tablet/phone)
- Offline sync, hardware, payments, security
- Cross-channel omnichannel invariants
- Roadmap alignment with Volume 15

## Out of Scope

- iOS App Store launch (Phase 2 gate)
- Restaurant/table POS
- Fiscal printer certification

---

## 1. Roadmap Alignment (Volume 15)

Volume 18 **implements** the following roadmap chapters for H4 Omnichannel delivery:

| Volume 15 Chapter | Volume 18 Implementation | Gate |
|-------------------|--------------------------|------|
| [Ch.02 — Mobile React Native](../15-future-roadmap/02-mobile-react-native.md) | Ch.02, Ch.03, Ch.04 | Shop + merchant Play Store NG |
| [Ch.03 — POS Omnichannel](../15-future-roadmap/03-pos-omnichannel.md) | Ch.01, Ch.05, Ch.06 | Unified inventory across channels |
| [Ch.09 — POS Module Specification](../15-future-roadmap/09-pos-module-specification.md) | Ch.05–Ch.08 | Lagos 5-merchant pilot |
| [Ch.10 — Mobile App Architecture](../15-future-roadmap/10-mobile-app-architecture.md) | Ch.02–Ch.04, Ch.09 | APK ≤ 45 MB; push p95 ≤ 30s |

---

## 2. Customer Shopping App (SCP Shop)

### Functional

- [ ] Published on Google Play Nigeria (`com.sapphital.shop`)
- [ ] Store bootstrap loads NGN catalog with theme tokens
- [ ] Guest checkout with email or `+234` phone completes
- [ ] Registered customer checkout with saved address
- [ ] Paystack sandbox: card payment completes via Custom Tab
- [ ] Paystack sandbox: USSD shows pending → confirmed on webhook
- [ ] Paystack sandbox: bank transfer pending → confirmed on webhook
- [ ] Deep link `sapphital://shop/checkout/return` validates HMAC token
- [ ] Order list and detail for authenticated customer
- [ ] Push: order paid and shipped (transactional)
- [ ] Marketing push blocked without `marketing_consent=true`
- [ ] NDPA: export and delete account flows reachable from settings
- [ ] Marketplace multi-vendor cart displays vendor grouping
- [ ] Bottom navigation is Home, Categories, Search, Wishlist, Account; cart remains one tap away
- [ ] PDP sticky Add to Cart does not overlap navigation, AI, or consent controls

### Non-Functional

- [ ] Cold start ≤ 3.0s on Tecno Camon 8 (Android 11)
- [ ] Home feed ≤ 2.0s on 4G (Lagos profile)
- [ ] Checkout user journey ≤ 60s (NFR-012)
- [ ] Touch targets ≥ 44×44dp (NFR-051)
- [ ] Base APK/AAB ≤ 45 MB
- [ ] Crash-free sessions ≥ 99.5% over 7-day beta
- [ ] 60fps scroll on PLP with 100 items

### Security

- [ ] No PAN storage; WebView cookies cleared post-checkout
- [ ] TLS pinning on production API
- [ ] Logout wipes cart session and tokens

---

## 3. Merchant Admin App (SCP Merchant)

### Functional

- [ ] Published on Google Play Nigeria (`com.sapphital.merchant`)
- [ ] Owner MFA enrollment on first device
- [ ] Dashboard shows today orders, NGN revenue, online vs POS split
- [ ] Order list filter by status and date
- [ ] Fulfillment flow creates shipment visible on web admin
- [ ] Order cancel with reason code (manager role)
- [ ] Refund initiate returns async status
- [ ] Product quick edit: title, status, price
- [ ] Stock adjustment ±999 reflects in inventory API
- [ ] Low stock push when `available <= threshold`
- [ ] New order push within 30s p95 of `OrderCreated`
- [ ] POS device list and remote revoke
- [ ] Multi-store switch clears stale cache
- [ ] Offline: read-only orders ≤ 24h with banner

### RBAC

- [ ] Fulfillment role cannot cancel (API 403)
- [ ] View-only role cannot adjust stock (API 403)
- [ ] Sensitive actions re-prompt biometric or password

### Non-Functional

- [ ] Push delivery ≥ 95% in Nigeria beta cohort
- [ ] Dashboard refresh ≤ 5s on 3G

---

## 4. POS Register App (SCP POS)

### Device & Shift Lifecycle

- [ ] Device register → manager activate → open shift E2E
- [ ] One open shift per register enforced
- [ ] Staff PIN login per shift; lockout after 5 failures
- [ ] Close shift produces Z-report matching sale totals ± ₦0
- [ ] Revoked device cannot sync; local wipe on reconnect

### Sales & Catalog

- [ ] Barcode HID scan adds line < 500ms local lookup (p95)
- [ ] Camera barcode fallback on Android 10+
- [ ] Product search by title/SKU online
- [ ] Supervisor PIN required for discount > 10%
- [ ] Completed sale creates `Order` with `channel=pos`
- [ ] Walk-out sale: `fulfillment_status=fulfilled`
- [ ] Void restores inventory within 30s online
- [ ] Refund requires supervisor PIN; online only
- [ ] Receipt number format `{store_code}-POS-{shift_seq}-{sale_seq}`
- [ ] Customer attach optional; receipt email/SMS with consent

### Omnichannel Inventory

- [ ] Online order reserves stock; POS reflects within 30s when online
- [ ] POS sale decrements same `inventory_levels` row as web
- [ ] Race test: last unit online + POS — first commit wins; loser gets oversell alert
- [ ] Idempotent replay of same `local_id` returns original order_id

---

## 5. Offline Sync (Lagos Profile)

- [ ] Cash sale completes in airplane mode
- [ ] 2-hour offline drill: sales queue; single order on reconnect
- [ ] Bank transfer sale offline with reference captured
- [ ] Duplicate sync batch returns same `order_id`
- [ ] Stock conflict surfaces manager UI; override syncs
- [ ] Price drift: server price wins with adjustment flag
- [ ] 72h offline triggers read-only; banner displayed
- [ ] Reconnect restores selling within 5 min sync
- [ ] Catalog full refresh on shift open; delta every 4h online
- [ ] Zero outbox loss on simulated app kill mid-sale
- [ ] SQLCipher encryption verified on device DB

---

## 6. Hardware (Lagos Pilot)

- [ ] HID Bluetooth scanner adds product from certified list
- [ ] ESC/POS 58mm Bluetooth receipt prints with QR reference
- [ ] Cash drawer kick on cash sale via printer pulse
- [ ] Printer disconnect shows non-blocking warning
- [ ] Email receipt delivered within 60s when customer email provided
- [ ] Certified device list (8 printers, 5 scanner modes) published
- [ ] Receipt masks phone: `+234 801 *** 678`

---

## 7. Payments — Nigeria & Kenya

### Nigeria (Phase 1 Blockers)

- [ ] Cash: ₦50,000 tender on ₦48,375 sale → ₦1,625 change
- [ ] Bank transfer: duplicate reference rejected within 24h per store
- [ ] Paystack Terminal sandbox charge end-to-end
- [ ] Paystack QR: success within 10s of webhook
- [ ] Paystack USSD: pending UI resolves on `charge.success`
- [ ] Split payment (cash + Paystack) equals exact total
- [ ] Terminal timeout 120s cancels and allows retry
- [ ] Digital payment blocked offline with clear cashier message
- [ ] Webhook signature verified before sale completion

### Kenya (Expansion Gate)

- [ ] M-Pesa STK sandbox on Kenya test store
- [ ] Phone `254XXXXXXXXX` validation
- [ ] STK timeout 90s with retry path
- [ ] KES-only on KE stores

---

## 8. Security & NDPA

- [ ] OWASP MASVS L1 checklist 100% for all three apps
- [ ] MobSF scan: zero critical per release
- [ ] Tenant isolation: zero cross-tenant on mobile API suite
- [ ] RoPA entry `MOB-001` signed by DPO
- [ ] Customer data export from shop app within 48h
- [ ] Paystack listed as subprocessor (NFR-083)
- [ ] No PAN in codebase (static analysis CI gate)
- [ ] Screenshot blocked on PIN and payment QR screens
- [ ] Remote wipe E2E within 5 min of revoke

---

## 9. Integration & Events

- [ ] `OrderCreated` from POS identical schema to online (plus `channel`, `register_id`)
- [ ] `PosSaleCompleted` webhook to developer platform (Volume 12)
- [ ] Merchant app shows POS revenue in dashboard channel split
- [ ] Inventory adjust from merchant app visible at POS within 30s

---

## 10. Testing Evidence Package

| Artifact | Owner | Required |
|----------|-------|----------|
| Detox E2E shop checkout (Paystack test) | Mobile QA | ✅ |
| Detox E2E POS offline → sync | Mobile QA | ✅ |
| Device farm: Samsung A14, Tecno Camon 8 | Mobile QA | ✅ |
| 3G throttling profile results | Mobile QA | ✅ |
| Lagos pilot merchant sign-off (5 stores) | Operations | ✅ |
| Pen test mobile summary | Security | ✅ |
| DPO NDPA sign-off | Legal/DPO | ✅ |

---

## 11. Lagos Pilot Exit Criteria

| Metric | Target | Measurement |
|--------|--------|-------------|
| Sale sync after reconnect | 99% within 5 min | Server metrics 14 days |
| Payment mix recorded | Cash/transfer/Paystack tracked | Z-report aggregate |
| Cash drawer variance | < 2% shifts with variance > ₦500 | Shift reports |
| Cashier training completion | 100% staff PIN enrolled | Store audit |
| Merchant NPS (pilot) | ≥ 40 | Survey post-pilot |
| Critical bugs open | 0 P0, ≤ 2 P1 | Issue tracker |

---

## 12. Phase 2 Gates (Not Phase 1 Blockers)

- [ ] iOS App Store submission (shop + merchant)
- [ ] Certificate pinning on custom domains
- [ ] Root/jailbreak block on checkout (warn-only in P1)
- [ ] PWA POS fallback for pop-up vendors
- [ ] Restaurant/table management module

---

## Sign-Off

Volume 18 is **complete for Phase 1 Nigeria launch** when all Section 2–8 criteria pass and Lagos pilot exit criteria (Section 11) are met.

| Role | Responsibility |
|------|----------------|
| Lead Architect | Technical acceptance |
| Mobile Engineering Lead | App quality and performance |
| Commerce Engineering Lead | Omnichannel inventory |
| Product Lead | Persona journey coverage |
| DPO | NDPA mobile flows |
| Operations Lead | Lagos pilot execution |

---

## References

- [Volume 18 README](./README.md)
- [Volume 13 — Testing](../13-testing/README.md)
- [Volume 15 Ch.02 — Mobile React Native](../15-future-roadmap/02-mobile-react-native.md)
- [Volume 15 Ch.03 — POS Omnichannel](../15-future-roadmap/03-pos-omnichannel.md)
- [Volume 15 Ch.09 — POS Module Specification](../15-future-roadmap/09-pos-module-specification.md)
- [Volume 15 Ch.10 — Mobile App Architecture](../15-future-roadmap/10-mobile-app-architecture.md)
- [Volume 21 Ch.05 — Phase 1 Payments Nigeria](../21-implementation-playbooks/05-phase1-payments-nigeria-playbook.md)
